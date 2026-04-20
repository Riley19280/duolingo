<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\User;
use App\Models\UserSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $userSectionMap = UserSection::where('user_id', $user->id)
            ->get()
            ->keyBy('section_id');

        $sections = Section::withCount('words')
            ->orderBy('section_number')
            ->orderBy('unit_number')
            ->get()
            ->map(function (Section $section) use ($userSectionMap) {
                $userSection = $userSectionMap->get($section->id);

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'sectionNumber' => $section->section_number,
                    'unitNumber' => $section->unit_number,
                    'wordsCount' => $section->words_count,
                    'isUnlocked' => $userSection?->is_unlocked ?? false,
                ];
            });

        return Inertia::render('sections/index', [
            'sections' => $sections,
        ]);
    }

    public function show(Section $section): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $words = $section->words()
            ->select('words.*', 'user_word.is_available')
            ->leftJoin('user_word', fn ($j) => $j->on('words.id', '=', 'user_word.word_id')
                ->where('user_word.user_id', $user->id))
            ->orderBy('words.text')
            ->get();

        return Inertia::render('sections/show', [
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
                'sectionNumber' => $section->section_number,
                'unitNumber' => $section->unit_number,
            ],
            'words' => $words->map(fn ($w) => [
                'text' => $w->text,
                'pinyin' => $w->pinyin,
                'translation' => $w->translation,
                'isAvailable' => (bool) $w->is_available,
                'ttsUrl' => $w->public_tts_url,
            ]),
        ]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'is_unlocked' => ['required', 'boolean'],
        ]);

        DB::table('user_section')->updateOrInsert(
            ['user_id' => $user->id, 'section_id' => $section->id],
            ['is_unlocked' => $validated['is_unlocked'], 'updated_at' => now(), 'created_at' => now()]
        );

        $wordIds = $section->words()->pluck('words.id');

        if ($validated['is_unlocked']) {
            $user->words()->syncWithoutDetaching(
                $wordIds->mapWithKeys(fn ($id) => [$id => ['is_available' => true]])->all()
            );
        } else {
            $user->words()->detach($wordIds);
        }

        return back();
    }
}
