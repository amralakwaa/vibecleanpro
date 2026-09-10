<?php

namespace App\Models;

use Database\Factories\AreaGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'sort_order'])]
class AreaGroup extends Model
{
    /** @use HasFactory<AreaGroupFactory> */
    use HasFactory;

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
}
