<?php

namespace App\Console\Commands;

use App\Library\VocabularyStats;
use App\Models\Section;
use App\Models\Word;
use App\Models\WordToken;
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

        $this->info('Found '.count($files).' file(s). Importing…');
        $this->newLine();

        foreach ($files as $file) {
            $this->importFile($file);
        }

        // ── Summary ───────────────────────────────────────────────────────────

        $summary = $stats->summary();

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Unique words', $summary['uniqueWords']],
                ['Unique characters', $summary['uniqueCharacters']],
                ...$summary['byState']->map(fn ($count, $state) => [ucfirst(strtolower($state)), $count])->values()->all(),
            ]
        );

        return self::SUCCESS;
    }

    private function importFile(string $file): void
    {
        $requests = json_decode(file_get_contents($file), associative: true);

        if (! is_array($requests)) {
            $this->warn('Skipping invalid JSON: '.basename($file));

            return;
        }

        $this->line('  Processing <fg=cyan>'.basename($file).'</> ('.count($requests).' request(s))');

        DB::transaction(function () use ($requests) {
            foreach ($requests as $request) {
                $sections = Arr::get($request, 'responseBody.sections', []);

                foreach ($sections as $sectionData) {
                    $section = $this->upsertSection($sectionData);

                    $wordIds = [];

                    foreach (Arr::get($sectionData, 'words', []) as $wordData) {
                        $word = $this->upsertWord($wordData);
                        $wordIds[] = $word->id;
                    }

                    // Attach all words to this section without removing existing links
                    $section->words()->syncWithoutDetaching($wordIds);
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
                'state' => Arr::get($wordData, 'state', 'LOCKED'),
            ]
        );

        // Only write tokens when the word is new — existing words already have them
        if ($word->wasRecentlyCreated) {
            $tokens = collect(Arr::get($wordData, 'transliterationObj.tokens', []))
                ->map(fn (array $token, int $position) => [
                    'word_id' => $word->id,
                    'character' => $token['token'],
                    'pinyin' => Arr::get($token, 'transliterationTexts.0.text', ''),
                    'position' => $position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            WordToken::insert($tokens);
        }

        return $word;
    }
}
