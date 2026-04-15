<?php

namespace App\Http\Controllers;

use App\Models\PracticeSet;
use App\Models\Section;
use App\Models\User;
use App\Models\UserSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PracticeSetController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('practice/sets/form', [
            'sections' => $this->sectionsWithWords(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'word_ids' => ['required', 'array', 'min:1'],
            'word_ids.*' => ['integer', 'exists:words,id'],
        ]);

        $set = PracticeSet::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
        ]);

        $set->words()->sync($validated['word_ids']);

        return redirect()->route('practice.index');
    }

    public function edit(PracticeSet $practiceSet): Response
    {
        $this->authorize('view', $practiceSet);

        return Inertia::render('practice/sets/form', [
            'practiceSet' => [
                'id' => $practiceSet->id,
                'name' => $practiceSet->name,
                'wordIds' => $practiceSet->words()->pluck('words.id'),
            ],
            'sections' => $this->sectionsWithWords(),
        ]);
    }

    public function update(Request $request, PracticeSet $practiceSet): RedirectResponse
    {
        $this->authorize('update', $practiceSet);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'word_ids' => ['required', 'array', 'min:1'],
            'word_ids.*' => ['integer', 'exists:words,id'],
        ]);

        $practiceSet->update(['name' => $validated['name']]);
        $practiceSet->words()->sync($validated['word_ids']);

        return redirect()->route('practice.index');
    }

    public function destroy(PracticeSet $practiceSet): RedirectResponse
    {
        $this->authorize('delete', $practiceSet);

        $practiceSet->delete();

        return redirect()->route('practice.index');
    }

    private function sectionsWithWords(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $userSectionMap = UserSection::where('user_id', $user->id)
            ->get()
            ->keyBy('section_id');

        return Section::orderBy('section_number')
            ->orderBy('unit_number')
            ->with(['words' => fn ($q) => $q->select('words.id', 'text', 'pinyin', 'translation')->orderBy('text')])
            ->get()
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'title' => $section->title,
                'isUnlocked' => (bool) ($userSectionMap->get($section->id)?->is_unlocked ?? false),
                'words' => $section->words->map(fn ($w) => [
                    'id' => $w->id,
                    'text' => $w->text,
                    'pinyin' => $w->pinyin,
                    'translation' => $w->translation,
                ]),
            ])
            ->all();
    }
}
