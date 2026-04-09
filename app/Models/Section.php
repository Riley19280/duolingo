<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['duolingo_id', 'title', 'section_number', 'unit_number'])]
class Section extends Model
{
    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class);
    }
}
