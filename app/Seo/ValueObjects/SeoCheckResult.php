<?php

namespace App\Seo\ValueObjects;

use App\Seo\Enums\CheckSeverity;

final readonly class SeoCheckResult
{
    public function __construct(
        public string $key,
        public CheckSeverity $severity,
        public string $message,
    ) {}
}
