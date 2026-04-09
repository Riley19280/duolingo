<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['text', 'translation', 'pinyin', 'tts_url', 'state'])]
class Word extends Model
{
    public function tokens(): HasMany
    {
        return $this->hasMany(WordToken::class)->orderBy('position');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class);
    }
}
