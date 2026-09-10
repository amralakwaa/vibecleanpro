<?php

namespace App\Seo\ValueObjects;

use App\Seo\Enums\CheckSeverity;
use Illuminate\Support\Collection;

final readonly class PublishingGateResult
{
    /**
     * @param  array<int, SeoCheckResult>  $checks
     */
    public function __construct(public array $checks) {}

    /**
     * @return Collection<int, SeoCheckResult>
     */
    public function errors(): Collection
    {
        return collect($this->checks)->where('severity', CheckSeverity::Error)->values();
    }

    /**
     * @return Collection<int, SeoCheckResult>
     */
    public function warnings(): Collection
    {
        return collect($this->checks)->where('severity', CheckSeverity::Warning)->values();
    }

    /**
     * @return Collection<int, SeoCheckResult>
     */
    public function passes(): Collection
    {
        return collect($this->checks)->where('severity', CheckSeverity::Pass)->values();
    }

    public function canPublish(): bool
    {
        return $this->errors()->isEmpty();
    }
}
