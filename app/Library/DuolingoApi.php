<?php

namespace App\Library;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DuolingoApi
{
    private const string BASE_URL = 'https://www-prod.duolingo.com/2017-06-30';

    private ?string $jwt;

    private ?string $alphabetsKey;

    public function __construct()
    {
        $this->jwt = config('services.duolingo.jwt');
        $this->alphabetsKey = config('services.duolingo.alphabets_key');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->jwt) && ! empty($this->alphabetsKey);
    }

    /**
     * Fetch and save character data to storage/app/raw if not already present.
     * Returns true if fetched, false if the file already existed.
     *
     * @throws \RuntimeException if the request fails
     */
    public function fetchCharacterIfNeeded(string $character): bool
    {
        $path = storage_path("app/raw/{$character}.json");

        if (File::exists($path)) {
            return false;
        }

        $response = $this->fetchCharacter($character);

        if (! $response->successful()) {
            throw new \RuntimeException("Duolingo API returned {$response->status()} for '{$character}'.");
        }

        File::put($path, $response->body());

        return true;
    }

    public function fetchCharacter(string $character): Response
    {
        return $this->request("alphabets/courses/zh/en/expandedViewInfo/{$character}", [
            'alphabetsPathProgressKey' => $this->alphabetsKey,
            'expandedViewId' => $character,
            'fromLanguage' => 'en',
            'learningLanguage' => 'zh',
        ]);
    }

    private function request(string $path, array $query = []): Response
    {
        return Http::timeout(15)
            ->connectTimeout(5)
            ->withToken($this->jwt)
            ->get(self::BASE_URL."/{$path}", $query);
    }
}
