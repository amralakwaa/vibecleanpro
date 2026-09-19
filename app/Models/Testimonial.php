<?php

namespace App\Models;

use App\Enums\TestimonialSource;
use App\Observers\TestimonialObserver;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['area_id', 'service_id', 'author_name', 'rating', 'content', 'source', 'source_ref', 'consent_confirmed', 'approved_at', 'is_featured', 'sort_order'])]
#[ObservedBy(TestimonialObserver::class)]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'source' => TestimonialSource::class,
            'consent_confirmed' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Only approved reviews may appear on a public page.
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->whereNotNull('approved_at');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
