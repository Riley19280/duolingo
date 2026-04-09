<?php

namespace App\Library;

use App\Models\Character;
use App\Models\Section;
use App\Models\User;
use App\Models\UserWord;
use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VocabularyStats
{
    /**
     * Total number of unique words in the database.
     */
    public function uniqueWordCount(): int
    {
        return Word::count();
    }

    /**
     * Number of distinct Han characters across the vocabulary.
     */
    public function uniqueCharacterCount(): int
    {
        return Character::count();
    }

    /**
     * Word counts grouped by state (AVAILABLE, LOCKED, …).
     * Optionally scoped to a single user.
     *
     * @return Collection<string, int>
     */
    /**
     * Word counts split by availability.
     * Returns a collection with 'available' and 'locked' keys.
     *
     * @return Collection<string, int>
     */
    public function wordsByAvailability(?User $user = null): Collection
    {
        return UserWord::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->select('is_available', DB::raw('count(*) as total'))
            ->groupBy('is_available')
            ->get()
            ->pluck('total', 'is_available')
            ->mapWithKeys(fn ($total, $isAvailable) => [$isAvailable ? 'available' : 'locked' => $total]);
    }

    /**
     * All words that contain the given Han character.
     *
     * @return Collection<int, Word>
     */
    public function wordsContaining(string $character): Collection
    {
        return Word::whereHas(
            'characters.character',
            fn ($query) => $query->where('character', $character)
        )->orderBy('text')->get();
    }

    /**
     * Sections ordered by section then unit number, each with a word count.
     *
     * @return Collection<int, Section>
     */
    public function wordsBySection(): Collection
    {
        return Section::withCount('words')
            ->orderBy('section_number')
            ->orderBy('unit_number')
            ->get();
    }

    /**
     * Characters that appear in the most words, descending.
     *
     * @return Collection<int, Character>
     */
    public function topCharacters(int $limit = 20): Collection
    {
        return Character::withCount('wordCharacters as word_count')
            ->orderByDesc('word_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Full summary suitable for console output.
     *
     * @return array{uniqueWords: int, uniqueCharacters: int, byState: Collection<string, int>}
     */
    public function summary(): array
    {
        return [
            'uniqueWords' => $this->uniqueWordCount(),
            'uniqueCharacters' => $this->uniqueCharacterCount(),
            'byAvailability' => $this->wordsByAvailability(),
        ];
    }
}
