<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['text', 'translation', 'pinyin', 'tts_url'])]
class Word extends Model
{
    use HasFactory;

    protected function publicTtsUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $path = public_path("tts/{$this->text}.mp3");

                if (! file_exists($path)) {
                    return null;
                }

                if (config('nativephp-internal.running')) {
                    return 'data:audio/mpeg;base64,'.base64_encode(file_get_contents($path));
                }

                return asset("tts/{$this->text}.mp3");
            },
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
