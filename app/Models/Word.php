<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['text', 'translation', 'pinyin', 'tts_url'])]
class Word extends Model
{
    use HasFactory;

    public function characters(): HasMany
    {
        return $this->hasMany(WordCharacter::class)->orderBy('position');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(UserWord::class)->withPivot('is_available')->withTimestamps();
    }
}
