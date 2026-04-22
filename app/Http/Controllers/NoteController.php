<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Word;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Note::class, 'note');
    }

    public function update(Request $request, Note $note): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $note->update($validated);

        return back();
    }

    public function store(Request $request, Word $word): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        Note::create([
            'user_id' => auth()->id(),
            'notable_type' => Word::class,
            'notable_id' => $word->id,
            'content' => $validated['content'],
        ]);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return back();
    }
}
