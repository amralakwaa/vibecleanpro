<?php

namespace App\Models\Concerns;

use App\Models\Page;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Shared by every model that can back a publishable Page (Service, Area,
 * Project, Article, Offer).
 *
 * Aggregate ownership: the domain entity (e.g. Service) is the aggregate
 * root and owns the lifecycle. Its Page is a dependent record that always
 * follows: soft-delete, restore, and force-delete on the entity cascade to
 * the Page in the same direction. The Page model itself defines no reverse
 * hook back onto its pageable entity, so this cascade only ever runs one
 * way and cannot loop.
 */
trait HasPage
{
    public function page(): MorphOne
    {
        return $this->morphOne(Page::class, 'pageable');
    }

    protected static function bootHasPage(): void
    {
        static::deleting(function ($model) {
            $page = $model->page()->withTrashed()->first();

            if (! $page) {
                return;
            }

            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                $page->forceDelete();
            } else {
                $page->delete();
            }
        });

        static::restored(function ($model) {
            $page = $model->page()->onlyTrashed()->first();

            $page?->restore();
        });
    }
}
