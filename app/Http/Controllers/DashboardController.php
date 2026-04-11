<?php

namespace App\Http\Controllers;

use App\Library\VocabularyStats;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(VocabularyStats $stats): Response
    {
        /** @var User $user */
        $user = auth()->user();
        $summary = $stats->summary();
        $availability = $summary['byAvailability'];

        return Inertia::render('dashboard', [
            'uniqueWords' => $summary['uniqueWords'],
            'uniqueCharacters' => $summary['uniqueCharacters'],
            'availableWords' => $availability['available'] ?? 0,
            'lockedWords' => $availability['locked'] ?? 0,
            'sectionsCovered' => $stats->sectionsCovered($user),
            'totalSections' => Section::count(),
            'userCharacters' => $stats->userCharacters($user)->map(fn ($c) => [
                'character' => $c->character,
            ]),
            'availableWordList' => $stats->availableWords($user)->map(fn ($w) => [
                'text' => $w->text,
                'pinyin' => $w->pinyin,
                'translation' => $w->translation,
                'ttsUrl' => Storage::disk('public')->exists("tts/{$w->text}.mp3")
                    ? Storage::disk('public')->url("tts/{$w->text}.mp3")
                    : null,
            ]),
        ]);
    }
}
