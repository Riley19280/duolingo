<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['character', 'strokes'])]
class Character extends Model
{
    protected function casts(): array
    {
        return [
            'strokes' => 'array',
        ];
    }

    public function wordCharacters(): HasMany
    {
        return $this->hasMany(WordCharacter::class);
    }
}
