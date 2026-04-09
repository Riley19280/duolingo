<?php

namespace App\Console\Commands;

use App\Library\VocabularyStats;
use App\Models\Character;
use App\Models\Section;
use App\Models\User;
use App\Models\UserWord;
use App\Models\Word;
use App\Models\WordCharacter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

#[Signature('duolingo:import')]
#[Description('Import all captured request files from storage/app/raw into the database')]
class ImportDuolingoDataCommand extends Command
{
    public function handle(VocabularyStats $stats): int
    {
        $rawPath = storage_path('app/raw');

        if (! is_dir($rawPath)) {
            $this->error("Raw directory not found: {$rawPath}");

            return self::FAILURE;
        }

        $files = glob("{$rawPath}/*.json");

        if (empty($files)) {
            $this->warn('No JSON files found in storage/app/raw.');

            return self::SUCCESS;
        }

        $user = User::first();

        if (! $user) {
            $this->error('No users found — run the user seeder first.');

            return self::FAILURE;
        }

        $this->info('Found '.count($files).' file(s). Importing…');
        $this->newLine();

        foreach ($files as $file) {
            $this->importFile($file, $user);
        }

        $summary = $stats->summary();

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Unique words', $summary['uniqueWords']],
                ['Unique characters', $summary['uniqueCharacters']],
            ]
        );

        return self::SUCCESS;
    }

    private function importFile(string $file, User $user): void
    {
        $requests = json_decode(file_get_contents($file), associative: true);

        if (! is_array($requests)) {
            $this->warn('Skipping invalid JSON: '.basename($file));

            return;
        }

        $this->line('  Processing <fg=cyan>'.basename($file).'</> ('.count($requests).' request(s))');

        DB::transaction(function () use ($requests, $user) {
            foreach ($requests as $request) {
                $sections = Arr::get($request, 'responseBody.sections', []);

                foreach ($sections as $sectionData) {
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

                $strokeData = Arr::get($request, 'responseBody.strokeData');
                $queriedChar = Arr::get($request, 'responseBody.title');

                if ($strokeData && $queriedChar) {
                    Character::where('character', $queriedChar)
                        ->whereNull('strokes')
                        ->update(['strokes' => json_encode($strokeData)]);
                }
            }
        });
    }

    private function upsertSection(array $sectionData): Section
    {
        $title = Arr::get($sectionData, 'title', '');
        $sectionNumber = 0;
        $unitNumber = 0;

        if (preg_match('/Section (\d+), Unit (\d+)/i', $title, $matches)) {
            $sectionNumber = (int) $matches[1];
            $unitNumber = (int) $matches[2];
        }

        return Section::updateOrCreate(
            ['duolingo_id' => (string) Arr::get($sectionData, 'id')],
            [
                'title' => $title,
                'section_number' => $sectionNumber,
                'unit_number' => $unitNumber,
            ]
        );
    }

    private function upsertWord(array $wordData): Word
    {
        $word = Word::updateOrCreate(
            ['text' => Arr::get($wordData, 'text')],
            [
                'translation' => Arr::get($wordData, 'translation'),
                'pinyin' => Arr::get($wordData, 'transliteration'),
                'tts_url' => Arr::get($wordData, 'tts'),
            ]
        );

        if ($word->wasRecentlyCreated) {
            $now = now();
            $tokens = collect(Arr::get($wordData, 'transliterationObj.tokens', []))
                ->map(fn (array $token, int $position) => [
                    'word_id' => $word->id,
                    'character_id' => Character::firstOrCreate(
                        ['character' => $token['token']],
                        ['created_at' => $now, 'updated_at' => $now],
                    )->id,
                    'pinyin' => Arr::get($token, 'transliterationTexts.0.text', ''),
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all();

            WordCharacter::insert($tokens);
        }

        return $word;
    }
}
