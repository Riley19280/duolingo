<?php

namespace App\Http\Controllers;

use App\Enums\AnswerForm;
use App\Enums\ExerciseType;
use App\Enums\QuestionForm;
use App\Models\PracticeAttempt;
use App\Models\PracticeSession;
use App\Models\PracticeSet;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PracticeSessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'practice_set_id' => ['required', 'integer', 'exists:practice_sets,id'],
            'exercise_type' => ['required', 'string', 'in:'.implode(',', array_column(ExerciseType::cases(), 'value'))],
            'question_form' => ['required', 'string', 'in:'.implode(',', array_column(QuestionForm::cases(), 'value'))],
            'answer_form' => ['required', 'string', 'in:'.implode(',', array_column(AnswerForm::cases(), 'value'))],
        ]);

        $set = PracticeSet::where('id', $validated['practice_set_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $session = PracticeSession::create([
            'user_id' => $user->id,
            'practice_set_id' => $set->id,
            'exercise_type' => $validated['exercise_type'],
            'question_form' => $validated['question_form'],
            'answer_form' => $validated['answer_form'],
        ]);

        return redirect()->route('practice.sessions.show', $session);
    }

    public function show(PracticeSession $practiceSession): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_if($practiceSession->user_id !== $user->id, 403);

        $words = [];
        $results = null;

        if ($practiceSession->completed_at) {
            $results = $practiceSession->attempts()
                ->with('word:id,text,pinyin,translation')
                ->orderBy('id')
                ->get()
                ->map(fn (PracticeAttempt $a) => [
                    'wordId' => $a->word_id,
                    'word' => [
                        'text' => $a->word->text,
                        'pinyin' => $a->word->pinyin,
                        'translation' => $a->word->translation,
                    ],
                    'isCorrect' => $a->is_correct,
                    'givenAnswer' => $a->given_answer,
                    'correctAnswer' => $a->correct_answer,
                    'responseTimeMs' => $a->response_time_ms,
                ]);
        } else {
            $words = $practiceSession->practiceSet?->words()
                ->select('words.id', 'text', 'pinyin', 'translation', 'tts_url')
                ->get()
                ->shuffle()
                ->values() ?? collect();
        }

        return Inertia::render('practice/session', [
            'session' => [
                'id' => $practiceSession->id,
                'exerciseType' => $practiceSession->exercise_type,
                'questionForm' => $practiceSession->question_form,
                'answerForm' => $practiceSession->answer_form,
                'completedAt' => $practiceSession->completed_at,
            ],
            'words' => $words,
            'results' => $results,
        ]);
    }

    public function complete(Request $request, PracticeSession $practiceSession): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        abort_if($practiceSession->user_id !== $user->id, 403);

        $validated = $request->validate([
            'attempts' => ['required', 'array', 'min:1'],
            'attempts.*.word_id' => ['required', 'integer', 'exists:words,id'],
            'attempts.*.given_answer' => ['nullable', 'string', 'max:1000'],
            'attempts.*.correct_answer' => ['nullable', 'string', 'max:1000'],
            'attempts.*.is_correct' => ['required', 'boolean'],
            'attempts.*.response_time_ms' => ['nullable', 'integer', 'min:0'],
            'attempts.*.options' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($practiceSession, $validated): void {
            foreach ($validated['attempts'] as $data) {
                PracticeAttempt::create([
                    'practice_session_id' => $practiceSession->id,
                    'word_id' => $data['word_id'],
                    'exercise_type' => $practiceSession->exercise_type,
                    'question_form' => $practiceSession->question_form,
                    'answer_form' => $practiceSession->answer_form,
                    'given_answer' => $data['given_answer'] ?? null,
                    'correct_answer' => $data['correct_answer'],
                    'is_correct' => $data['is_correct'],
                    'response_time_ms' => $data['response_time_ms'] ?? null,
                    'options' => $data['options'] ?? null,
                ]);
            }

            $practiceSession->update(['completed_at' => now()]);
        });

        return redirect()->route('practice.sessions.show', $practiceSession);
    }
}
