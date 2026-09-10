<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Observers\PageObserver;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['type', 'title', 'slug', 'status', 'published_at', 'sort_order'])]
#[ObservedBy(PageObserver::class)]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function pageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function seoMetadata(): HasOne
    {
        return $this->hasOne(SeoMetadata::class);
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->orderBy('position');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class);
    }

    public function slugHistory(): HasMany
    {
        return $this->hasMany(SlugHistory::class);
    }

    public function linksFrom(): HasMany
    {
        return $this->hasMany(InternalLink::class, 'from_page_id');
    }

    public function linksTo(): HasMany
    {
        return $this->hasMany(InternalLink::class, 'to_page_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', PageStatus::Published)
            ->where(function (Builder $query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
