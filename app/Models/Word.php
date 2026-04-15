<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['text', 'translation', 'pinyin', 'tts_url'])]
class Word extends Model
{
    use HasFactory;

    protected function publicTtsUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk('public')->exists("tts/{$this->text}.mp3")
                ? Storage::disk('public')->url("tts/{$this->text}.mp3")
                : null,
        );
    }

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
