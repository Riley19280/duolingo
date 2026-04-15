<?php

namespace App\Http\Controllers;

use App\Enums\AnswerForm;
use App\Enums\ExerciseType;
use App\Enums\QuestionForm;
use App\Models\PracticeSession;
use App\Models\PracticeSet;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PracticeController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $sets = PracticeSet::where('user_id', $user->id)
            ->withCount('words')
            ->orderBy('name')
            ->get()
            ->map(fn (PracticeSet $set) => [
                'id' => $set->id,
                'name' => $set->name,
                'wordsCount' => $set->words_count,
            ]);

        $sessions = PracticeSession::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->with('practiceSet:id,name')
            ->withCount([
                'attempts',
                'attempts as correct_count' => fn ($q) => $q->where('is_correct', true),
            ])
            ->orderByDesc('completed_at')
            ->limit(50)
            ->get()
            ->map(fn (PracticeSession $s) => [
                'id' => $s->id,
                'setName' => $s->practiceSet?->name,
                'exerciseType' => $s->exercise_type->label(),
                'questionForm' => $s->question_form->label(),
                'answerForm' => $s->answer_form->label(),
                'attemptsCount' => $s->attempts_count,
                'correctCount' => $s->correct_count,
                'completedAt' => $s->completed_at->toIso8601String(),
            ]);

        return Inertia::render('practice/index', [
            'sets' => $sets,
            'sessions' => $sessions,
            'exerciseTypes' => array_map(fn (ExerciseType $e) => ['value' => $e->value, 'label' => $e->label()], ExerciseType::cases()),
            'questionForms' => array_map(fn (QuestionForm $q) => ['value' => $q->value, 'label' => $q->label()], QuestionForm::cases()),
            'answerForms' => array_map(fn (AnswerForm $a) => ['value' => $a->value, 'label' => $a->label()], AnswerForm::cases()),
        ]);
    }
}
