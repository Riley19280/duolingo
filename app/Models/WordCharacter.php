<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['word_id', 'character_id', 'pinyin', 'position'])]
class WordCharacter extends Model
{
    protected $table = 'word_characters';

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
