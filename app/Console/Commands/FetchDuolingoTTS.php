<?php

namespace App\Console\Commands;

use App\Models\Word;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

#[Signature('duolingo:fetch-tts {word? : Chinese text of a specific word to download}')]
#[Description('Download missing TTS audio files into public/tts')]
class FetchDuolingoTTS extends Command
{
    private const DIR = 'tts';

    public function handle(): int
    {
        $words = $this->wordsToDownload();

        if ($words->isEmpty()) {
            $this->info('All TTS files are already downloaded.');

            return self::SUCCESS;
        }

        $this->info("Downloading {$words->count()} file(s) into public/tts…");
        $this->newLine();

        $downloaded = 0;
        $failed = 0;

        $this->withProgressBar($words, function (Word $word) use (&$downloaded, &$failed) {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->get($word->tts_url);

            if ($response->successful()) {
                file_put_contents(public_path(self::DIR."/{$word->text}.mp3"), $response->body());
                $downloaded++;
            } else {
                $failed++;
                $this->newLine();
                $this->warn("  Failed [{$response->status()}]: {$word->text}");
            }
        });

        $this->newLine(2);
        $this->line("  <fg=green>✓</> Downloaded: {$downloaded}");

        if ($failed > 0) {
            $this->line("  <fg=red>✗</> Failed:     {$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return Collection<int, Word>
     */
    private function wordsToDownload(): Collection
    {
        $query = Word::whereNotNull('tts_url');

        if ($text = $this->argument('word')) {
            return $query->where('text', $text)->get();
        }

        return $query->get()->filter(
            fn (Word $word) => ! file_exists(public_path(self::DIR."/{$word->text}.mp3"))
        );
    }
}
