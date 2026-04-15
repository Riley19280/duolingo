<?php

namespace App\Models;

use App\Enums\AnswerForm;
use App\Enums\ExerciseType;
use App\Enums\QuestionForm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['practice_session_id', 'word_id', 'exercise_type', 'question_form', 'answer_form', 'options', 'given_answer', 'correct_answer', 'is_correct', 'response_time_ms'])]
class PracticeAttempt extends Model
{
    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'practice_session_id');
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    protected function casts(): array
    {
        return [
            'exercise_type' => ExerciseType::class,
            'question_form' => QuestionForm::class,
            'answer_form' => AnswerForm::class,
            'options' => 'array',
            'is_correct' => 'boolean',
        ];
    }
}
