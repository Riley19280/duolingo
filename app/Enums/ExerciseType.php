<?php

namespace App\Enums;

enum ExerciseType: string
{
    case MultipleChoice = 'multiple_choice';
    case Matching = 'matching';
    case Typing = 'typing';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple Choice',
            self::Matching => 'Matching',
            self::Typing => 'Typing',
        };
    }
}
