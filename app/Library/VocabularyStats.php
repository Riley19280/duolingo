<?php

namespace App\Library;

use App\Models\Section;
use App\Models\Word;
use App\Models\WordToken;
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
     * Number of distinct Han characters that appear across all word tokens.
     */
    public function uniqueCharacterCount(): int
    {
        return WordToken::distinct('character')->count('character');
    }

    /**
     * Word counts grouped by state (AVAILABLE, LOCKED, …).
     *
     * @return Collection<string, int>
     */
    public function wordsByState(): Collection
    {
        return Word::select('state', DB::raw('count(*) as total'))
            ->groupBy('state')
            ->orderBy('state')
            ->pluck('total', 'state');
    }

    /**
     * All words that contain the given character anywhere in their token list.
     *
     * @return Collection<int, Word>
     */
    public function wordsContaining(string $character): Collection
    {
        return Word::whereHas('tokens', fn ($query) => $query->where('character', $character))
            ->with('tokens')
            ->orderBy('text')
            ->get();
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
     * Top characters by number of words they appear in.
     *
     * @return Collection<int, object{character: string, word_count: int}>
     */
    public function topCharacters(int $limit = 20): Collection
    {
        return WordToken::select('character', DB::raw('count(distinct word_id) as word_count'))
            ->groupBy('character')
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
            'byState' => $this->wordsByState(),
        ];
    }
}
