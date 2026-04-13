<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['duolingo_id', 'title', 'section_number', 'unit_number'])]
class Section extends Model
{
    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(UserSection::class)->withPivot('is_unlocked')->withTimestamps();
    }

    public function userSections(): HasMany
    {
        return $this->hasMany(UserSection::class);
    }
}
