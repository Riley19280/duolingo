<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use App\Models\Word;
use Inertia\Inertia;
use Inertia\Response;

class WordController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $words = $user->words()
            ->orderBy('text')
            ->get()
            ->map(fn ($w) => [
                'id' => $w->id,
                'text' => $w->text,
                'pinyin' => $w->pinyin,
                'translation' => $w->translation,
                'ttsUrl' => $w->public_tts_url,
                'isAvailable' => (bool) $w->pivot->is_available,
            ]);

        $notes = Note::where('user_id', $user->id)
            ->where('notable_type', Word::class)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'wordId' => $n->notable_id,
                'content' => $n->content,
            ]);

        return Inertia::render('words/index', [
            'words' => $words,
            'notes' => $notes,
        ]);
    }
}
