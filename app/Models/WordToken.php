<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['word_id', 'character', 'pinyin', 'position'])]
class WordToken extends Model
{
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }
}
