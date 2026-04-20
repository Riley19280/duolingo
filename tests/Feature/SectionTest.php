<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\User;
use App\Models\UserSection;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    // --- Auth ---

    public function test_guests_are_redirected_from_sections_index(): void
    {
        $this->get(route('sections.index'))->assertRedirect(route('login'));
    }

    // --- Index ---

    public function test_sections_index_lists_sections_with_unlock_state(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();

        UserSection::create([
            'user_id' => $user->id,
            'section_id' => $section->id,
            'is_unlocked' => true,
        ]);

        $response = $this->actingAs($user)->get(route('sections.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('sections/index')
            ->where('sections', fn ($sections) => collect($sections)->contains(
                fn ($s) => $s['id'] === $section->id && $s['isUnlocked'] === true
            ))
        );
    }

    public function test_sections_index_shows_locked_when_no_user_section_record(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($user)->get(route('sections.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('sections', fn ($sections) => collect($sections)->contains(
                fn ($s) => $s['id'] === $section->id && $s['isUnlocked'] === false
            ))
        );
    }

    // --- Show ---

    public function test_section_show_lists_words_with_availability(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();
        $word = Word::factory()->create();
        $section->words()->attach($word->id);

        $user->words()->attach($word->id, ['is_available' => true]);

        $response = $this->actingAs($user)->get(route('sections.show', $section));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('sections/show')
            ->where('words.0.text', $word->text)
            ->where('words.0.isAvailable', true)
        );
    }

    // --- Update (lock/unlock) ---

    public function test_user_can_unlock_a_section(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();

        $this->actingAs($user)
            ->put(route('sections.update', $section), ['is_unlocked' => true])
            ->assertRedirect();

        $this->assertDatabaseHas(UserSection::class, [
            'user_id' => $user->id,
            'section_id' => $section->id,
            'is_unlocked' => true,
        ]);
    }

    public function test_user_can_lock_a_section(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();

        UserSection::create([
            'user_id' => $user->id,
            'section_id' => $section->id,
            'is_unlocked' => true,
        ]);

        $this->actingAs($user)
            ->put(route('sections.update', $section), ['is_unlocked' => false])
            ->assertRedirect();

        $this->assertDatabaseHas(UserSection::class, [
            'user_id' => $user->id,
            'section_id' => $section->id,
            'is_unlocked' => false,
        ]);
    }

    public function test_update_requires_is_unlocked_field(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();

        $this->actingAs($user)
            ->put(route('sections.update', $section), [])
            ->assertSessionHasErrors('is_unlocked');
    }

    public function test_unlocking_a_section_makes_its_words_available(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();
        $word = Word::factory()->create();
        $section->words()->attach($word->id);

        $this->actingAs($user)
            ->put(route('sections.update', $section), ['is_unlocked' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('user_word', [
            'user_id' => $user->id,
            'word_id' => $word->id,
            'is_available' => true,
        ]);
    }

    public function test_locking_a_section_removes_its_words(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create();
        $word = Word::factory()->create();
        $section->words()->attach($word->id);
        $user->words()->attach($word->id, ['is_available' => true]);

        UserSection::create([
            'user_id' => $user->id,
            'section_id' => $section->id,
            'is_unlocked' => true,
        ]);

        $this->actingAs($user)
            ->put(route('sections.update', $section), ['is_unlocked' => false])
            ->assertRedirect();

        $this->assertDatabaseMissing('user_word', [
            'user_id' => $user->id,
            'word_id' => $word->id,
        ]);
    }
}
