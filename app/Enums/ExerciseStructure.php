<?php

namespace App\Enums;

enum ExerciseStructure: string
{
    case Word = 'word';
    case Sentence = 'sentence';

    public function label(): string
    {
        return match ($this) {
            self::Word => 'Word',
            self::Sentence => 'Sentence',
        };
    }
}
