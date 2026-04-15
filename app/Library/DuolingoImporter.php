<?php

namespace App\Library;

use App\Models\Character;
use App\Models\Section;
use App\Models\User;
use App\Models\UserWord;
use App\Models\Word;
use App\Models\WordCharacter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DuolingoImporter
{
    /**
     * Process a single decoded response body object.
     */
    public function processResponseBody(array $data, User $user): void
    {
        DB::transaction(function () use ($data, $user) {
            foreach (Arr::get($data, 'sections', []) as $sectionData) {
                $section = $this->upsertSection($sectionData);

                $wordIds = [];

                foreach (Arr::get($sectionData, 'words', []) as $wordData) {
                    $word = $this->upsertWord($wordData);
                    $wordIds[] = $word->id;

                    UserWord::updateOrCreate(
                        ['user_id' => $user->id, 'word_id' => $word->id],
                        ['is_available' => Arr::get($wordData, 'state') === 'AVAILABLE'],
                    );
                }

                $section->words()->syncWithoutDetaching($wordIds);
            }

            $strokeData = Arr::get($data, 'strokeData');
            $queriedChar = Arr::get($data, 'title');

            if ($strokeData && $queriedChar) {
                Character::where('character', $queriedChar)
                    ->whereNull('strokes')
                    ->update(['strokes' => json_encode($strokeData)]);
            }
        });
    }

    /**
     * Sync the is_unlocked state for all sections based on whether every word
     * in the section has an available user_word record for the given user.
     */
    public function syncSectionUnlocks(User $user): void
    {
        Section::with('words:id')->get()->each(function (Section $section) use ($user) {
            $wordIds = $section->words->pluck('id');

            $allAvailable = $wordIds->isNotEmpty()
                && UserWord::where('user_id', $user->id)
                    ->whereIn('word_id', $wordIds)
                    ->where('is_available', true)
                    ->count() === $wordIds->count();

            DB::table('user_section')->updateOrInsert(
                ['user_id' => $user->id, 'section_id' => $section->id],
                ['is_unlocked' => $allAvailable, 'updated_at' => now(), 'created_at' => now()]
            );
        });
    }

    public function upsertSection(array $sectionData): Section
    {
        $title = Arr::get($sectionData, 'title', '');
        $sectionNumber = 0;
        $unitNumber = 0;

        if (preg_match('/Section (\d+), Unit (\d+)/i', $title, $matches)) {
            $sectionNumber = (int) $matches[1];
            $unitNumber = (int) $matches[2];
        }

        return Section::updateOrCreate(
            ['duolingo_id' => Arr::get($sectionData, 'id')],
            [
                'title' => $title,
                'section_number' => $sectionNumber,
                'unit_number' => $unitNumber,
            ]
        );
    }

    public function upsertWord(array $wordData): Word
    {
        $word = Word::updateOrCreate(
            ['text' => Arr::get($wordData, 'text')],
            array_filter([
                'translation' => Arr::get($wordData, 'translation'),
                'pinyin' => Arr::get($wordData, 'transliteration'),
                'tts_url' => Arr::get($wordData, 'tts'),
            ], fn ($v) => $v !== null)
        );

        if ($word->wasRecentlyCreated) {
            $tokens = collect(Arr::get($wordData, 'transliterationObj.tokens', []))
                ->map(fn (array $token, int $position) => [
                    'word_id' => $word->id,
                    'character_id' => Character::firstOrCreate(['character' => $token['token']], [])->id,
                    'pinyin' => Arr::get($token, 'transliterationTexts.0.text', ''),
                    'position' => $position,
                ])
                ->values()
                ->all();

            WordCharacter::insert($tokens);
        }

        return $word;
    }
}
