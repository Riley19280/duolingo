<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordTest extends TestCase
{
    use RefreshDatabase;

    // --- Auth ---

    public function test_guests_are_redirected_from_words_index(): void
    {
        $this->get(route('words.index'))->assertRedirect(route('login'));
    }

    // --- Words ---

    public function test_words_index_shows_all_users_words(): void
    {
        $user = User::factory()->create();
        $available = Word::factory()->create();
        $locked = Word::factory()->create();
        $user->words()->attach($available->id, ['is_available' => true]);
        $user->words()->attach($locked->id, ['is_available' => false]);

        $this->actingAs($user)->get(route('words.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('words/index')
                ->count('words', 2)
            );
    }

    public function test_words_index_marks_availability_correctly(): void
    {
        $user = User::factory()->create();
        $available = Word::factory()->create(['text' => 'aaa']);
        $locked = Word::factory()->create(['text' => 'bbb']);
        $user->words()->attach($available->id, ['is_available' => true]);
        $user->words()->attach($locked->id, ['is_available' => false]);

        $this->actingAs($user)->get(route('words.index'))
            ->assertInertia(fn ($page) => $page
                ->where('words.0.isAvailable', true)
                ->where('words.1.isAvailable', false)
            );
    }

    public function test_words_index_excludes_other_users_words(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $word = Word::factory()->create();
        $other->words()->attach($word->id, ['is_available' => true]);

        $this->actingAs($user)->get(route('words.index'))
            ->assertInertia(fn ($page) => $page->count('words', 0));
    }

    // --- Notes ---

    public function test_words_index_includes_users_notes(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();
        $user->words()->attach($word->id, ['is_available' => true]);

        Note::create([
            'user_id' => $user->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'My note',
        ]);

        $this->actingAs($user)->get(route('words.index'))
            ->assertInertia(fn ($page) => $page
                ->has('notes', 1)
                ->where('notes.0.content', 'My note')
            );
    }

    public function test_words_index_excludes_other_users_notes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $word = Word::factory()->create();

        Note::create([
            'user_id' => $other->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'Other note',
        ]);

        $this->actingAs($user)->get(route('words.index'))
            ->assertInertia(fn ($page) => $page->count('notes', 0));
    }
}
