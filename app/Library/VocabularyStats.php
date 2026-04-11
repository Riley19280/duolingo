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
     * All distinct characters that appear in a user's words.
     *
     * @return Collection<int, Character>
     */
    public function userCharacters(User $user): Collection
    {
        return Character::select('characters.*')
            ->join('word_characters', 'characters.id', '=', 'word_characters.character_id')
            ->join('user_word', fn ($j) => $j->on('word_characters.word_id', '=', 'user_word.word_id')
                ->where('user_word.user_id', $user->id)
                ->where('user_word.is_available', true))
            ->join('section_word', 'word_characters.word_id', '=', 'section_word.word_id')
            ->join('sections', 'section_word.section_id', '=', 'sections.id')
            ->groupBy('characters.id')
            ->orderByRaw('MIN(sections.section_number)')
            ->orderByRaw('MIN(sections.unit_number)')
            ->orderBy('characters.character')
            ->get();
    }

    /**
     * Number of sections that contain at least one of the user's available words.
     */
    public function sectionsCovered(User $user): int
    {
        $wordIds = $user->words()->wherePivot('is_available', true)->select('words.id');

        return Section::whereHas('words', fn ($q) => $q->whereIn('words.id', $wordIds))->count();
    }

    /**
     * Words marked as available for a user.
     *
     * @return Collection<int, Word>
     */
    public function availableWords(User $user): Collection
    {
        return Word::select('words.*')
            ->join('user_word', fn ($j) => $j->on('words.id', '=', 'user_word.word_id')
                ->where('user_word.user_id', $user->id)
                ->where('user_word.is_available', true))
            ->join('section_word', 'words.id', '=', 'section_word.word_id')
            ->join('sections', 'section_word.section_id', '=', 'sections.id')
            ->groupBy('words.id')
            ->orderByRaw('MIN(sections.section_number)')
            ->orderByRaw('MIN(sections.unit_number)')
            ->orderBy('words.text')
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
