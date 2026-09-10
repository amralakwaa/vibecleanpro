<?php

namespace App\Models;

use Database\Factories\ArticleCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'sort_order'])]
class ArticleCategory extends Model
{
    /** @use HasFactory<ArticleCategoryFactory> */
    use HasFactory;

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
