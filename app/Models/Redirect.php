<?php

namespace App\Models;

use App\Enums\RedirectSource;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['from_path', 'to_path', 'type', 'source', 'is_active'])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'source' => RedirectSource::class,
            'is_active' => 'boolean',
            'hits' => 'integer',
        ];
    }
}
