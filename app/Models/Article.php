<?php

namespace App\Models;

use App\Models\Concerns\HasPage;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['article_category_id', 'featured_media_id', 'title', 'excerpt'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasPage, SoftDeletes;

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'article_service')->withTimestamps();
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'article_area')->withTimestamps();
    }
}
