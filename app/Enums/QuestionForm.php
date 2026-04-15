<?php

namespace App\Enums;

enum QuestionForm: string
{
    case Character = 'character';
    case Pinyin = 'pinyin';
    case English = 'english';
    case Audio = 'audio';

    public function label(): string
    {
        return match ($this) {
            self::Character => 'Character',
            self::Pinyin => 'Pinyin',
            self::English => 'English',
            self::Audio => 'Audio',
        };
    }
}
