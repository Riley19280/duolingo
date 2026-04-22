<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    // --- Auth ---

    public function test_guests_cannot_store_a_note(): void
    {
        $word = Word::factory()->create();

        $this->post(route('words.notes.store', $word), ['content' => 'Hello'])
            ->assertRedirect(route('login'));
    }

    public function test_guests_cannot_delete_a_note(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $note = Note::create([
            'user_id' => $user->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'Protected',
        ]);

        $this->delete(route('notes.destroy', $note))
            ->assertRedirect(route('login'));
    }

    // --- Store ---

    public function test_user_can_create_a_note_for_a_word(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $this->actingAs($user)
            ->post(route('words.notes.store', $word), ['content' => 'My note'])
            ->assertRedirect();

        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'My note',
        ]);
    }

    public function test_store_requires_content(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $this->actingAs($user)
            ->post(route('words.notes.store', $word), ['content' => ''])
            ->assertSessionHasErrors('content');
    }

    // --- Update ---

    public function test_user_can_update_their_own_note(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $note = Note::create([
            'user_id' => $user->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'Original',
        ]);

        $this->actingAs($user)
            ->patch(route('notes.update', $note), ['content' => 'Updated'])
            ->assertRedirect();

        $this->assertSame('Updated', $note->fresh()->content);
    }

    public function test_user_cannot_update_another_users_note(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $word = Word::factory()->create();

        $note = Note::create([
            'user_id' => $other->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'Original',
        ]);

        $this->actingAs($user)
            ->patch(route('notes.update', $note), ['content' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame('Original', $note->fresh()->content);
    }

    public function test_update_requires_content(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $note = Note::create([
            'user_id' => $user->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'Original',
        ]);

        $this->actingAs($user)
            ->patch(route('notes.update', $note), ['content' => ''])
            ->assertSessionHasErrors('content');
    }

    // --- Destroy ---

    public function test_user_can_delete_their_own_note(): void
    {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $note = Note::create([
            'user_id' => $user->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'To delete',
        ]);

        $this->actingAs($user)
            ->delete(route('notes.destroy', $note))
            ->assertRedirect();

        $this->assertNull($note->fresh());
    }

    public function test_user_cannot_delete_another_users_note(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $word = Word::factory()->create();

        $note = Note::create([
            'user_id' => $other->id,
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => 'Protected',
        ]);

        $this->actingAs($user)
            ->delete(route('notes.destroy', $note))
            ->assertForbidden();

        $this->assertNotNull($note->fresh());
    }
}
