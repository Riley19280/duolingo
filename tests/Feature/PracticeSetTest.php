<?php

namespace Tests\Feature;

use App\Models\PracticeSet;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeSetTest extends TestCase
{
    use RefreshDatabase;

    // --- Auth ---

    public function test_guests_are_redirected_from_practice_index(): void
    {
        $this->get(route('practice.index'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('practice.sets.create'))->assertRedirect(route('login'));
    }

    // --- Index ---

    public function test_index_lists_only_the_users_own_sets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        PracticeSet::factory()->create(['user_id' => $user->id, 'name' => 'My Set']);
        PracticeSet::factory()->create(['user_id' => $other->id, 'name' => 'Other Set']);

        $response = $this->actingAs($user)->get(route('practice.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('practice/index')
            ->where('sets.0.name', 'My Set')
            ->count('sets', 1)
        );
    }

    // --- Create ---

    public function test_create_renders_form_with_sections(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('practice.sets.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('practice/sets/form')
            ->missing('practiceSet')
            ->has('sections')
        );
    }

    // --- Store ---

    public function test_user_can_create_a_practice_set(): void
    {
        $user = User::factory()->create();
        $words = Word::factory(3)->create();

        $this->actingAs($user)->post(route('practice.sets.store'), [
            'name' => 'Test Set',
            'word_ids' => $words->pluck('id')->all(),
        ])->assertRedirect(route('practice.index'));

        $set = PracticeSet::where('user_id', $user->id)->first();
        $this->assertNotNull($set);
        $this->assertSame('Test Set', $set->name);
        $this->assertCount(3, $set->words);
    }

    public function test_store_requires_a_name(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $this->actingAs($user)
            ->post(route('practice.sets.store'), ['word_ids' => [$word->id]])
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_at_least_one_word(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('practice.sets.store'), ['name' => 'Empty Set', 'word_ids' => []])
            ->assertSessionHasErrors('word_ids');
    }

    // --- Edit ---

    public function test_user_can_visit_edit_for_their_own_set(): void
    {
        $user = User::factory()->create();
        $set = PracticeSet::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('practice.sets.edit', $set));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('practice/sets/form')
            ->where('practiceSet.id', $set->id)
        );
    }

    public function test_user_cannot_edit_another_users_set(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $set = PracticeSet::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->get(route('practice.sets.edit', $set))
            ->assertForbidden();
    }

    // --- Update ---

    public function test_user_can_update_their_own_set(): void
    {
        $user = User::factory()->create();
        $set = PracticeSet::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
        $words = Word::factory(2)->create();

        $this->actingAs($user)->put(route('practice.sets.update', $set), [
            'name' => 'New Name',
            'word_ids' => $words->pluck('id')->all(),
        ])->assertRedirect(route('practice.index'));

        $this->assertSame('New Name', $set->fresh()->name);
        $this->assertCount(2, $set->fresh()->words);
    }

    public function test_user_cannot_update_another_users_set(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $set = PracticeSet::factory()->create(['user_id' => $other->id]);
        $word = Word::factory()->create();

        $this->actingAs($user)
            ->put(route('practice.sets.update', $set), ['name' => 'Hacked', 'word_ids' => [$word->id]])
            ->assertForbidden();
    }

    // --- Destroy ---

    public function test_user_can_delete_their_own_set(): void
    {
        $user = User::factory()->create();
        $set = PracticeSet::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('practice.sets.destroy', $set))
            ->assertRedirect(route('practice.index'));

        $this->assertNull($set->fresh());
    }

    public function test_user_cannot_delete_another_users_set(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $set = PracticeSet::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->delete(route('practice.sets.destroy', $set))
            ->assertForbidden();

        $this->assertNotNull($set->fresh());
    }
}
