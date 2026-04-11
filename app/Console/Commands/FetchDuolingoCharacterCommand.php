<?php

namespace App\Console\Commands;

use App\Library\DuolingoApi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('duolingo:fetch-character {character : The Chinese character to fetch}')]
#[Description('Fetch stroke data for a character from the Duolingo API and save to storage/app/raw')]
class FetchDuolingoCharacterCommand extends Command
{
    public function handle(DuolingoApi $api): int
    {
        if (! $api->isConfigured()) {
            $this->error('DUOLINGO_JWT and DUOLINGO_ALPHABETS_KEY must be set in your .env file.');

            return self::FAILURE;
        }

        $character = $this->argument('character');

        try {
            $fetched = $api->fetchCharacterIfNeeded($character);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $fetched
            ? $this->info("Saved to storage/app/raw/{$character}.json.")
            : $this->info("File already exists for '{$character}', skipping.");

        return self::SUCCESS;
    }
}
