<?php

namespace App\Models;

use App\Enums\AnswerForm;
use App\Enums\ExerciseType;
use App\Enums\QuestionForm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'practice_set_id', 'exercise_type', 'question_form', 'answer_form', 'completed_at'])]
class PracticeSession extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function practiceSet(): BelongsTo
    {
        return $this->belongsTo(PracticeSet::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    protected function casts(): array
    {
        return [
            'exercise_type' => ExerciseType::class,
            'question_form' => QuestionForm::class,
            'answer_form' => AnswerForm::class,
            'completed_at' => 'datetime',
        ];
    }
}
