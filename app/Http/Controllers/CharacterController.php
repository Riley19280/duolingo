<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CharacterController extends Controller
{
    public function show(Character $character): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $words = Word::select('words.*')
            ->join('word_characters', fn ($j) => $j->on('words.id', '=', 'word_characters.word_id')
                ->where('word_characters.character_id', $character->id))
            ->orderBy('words.text')
            ->get();

        $userWords = $user->words()->whereIn('words.id', $words->pluck('id'))->get()->keyBy('id');

        return Inertia::render('characters/show', [
            'character' => $character->character,
            'words' => $words->map(fn ($w) => [
                'text' => $w->text,
                'pinyin' => $w->pinyin,
                'translation' => $w->translation,
                'isAvailable' => $userWords->has($w->id) && $userWords->get($w->id)->pivot->is_available,
                'ttsUrl' => Storage::disk('public')->exists("tts/{$w->text}.mp3")
                    ? Storage::disk('public')->url("tts/{$w->text}.mp3")
                    : null,
            ]),
        ]);
    }
}
