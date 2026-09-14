<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A public form may only reference an entity the public can actually
 * see. "Visible" is decided by exactly one thing in this codebase - the
 * Page::published() scope the public controllers filter with - so this
 * rule runs the same whereHas('page', published) the Quote form used to
 * build its own select options. A draft, scheduled, review, or
 * soft-deleted Service/Area therefore fails validation instead of being
 * silently attached to a lead.
 *
 * @template TModel of Model
 */
class HasPublishedPage implements ValidationRule
{
    /**
     * @param  class-string<TModel>  $model  A model using the HasPage trait.
     */
    public function __construct(
        private readonly string $model,
        private readonly string $message,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isPublished = $this->model::query()
            ->whereKey($value)
            ->whereHas('page', fn ($query) => $query->published())
            ->exists();

        if (! $isPublished) {
            $fail($this->message);
        }
    }
}
