<?php

namespace App\Enums;

enum AnswerForm: string
{
    case Character = 'character';
    case Pinyin = 'pinyin';
    case English = 'english';

    public function label(): string
    {
        return match ($this) {
            self::Character => 'Character',
            self::Pinyin => 'Pinyin',
            self::English => 'English',
        };
    }
}
