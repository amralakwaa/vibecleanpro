<?php

namespace App\Seo\ValueObjects;

final readonly class RedirectResolution
{
    public function __construct(
        public int $redirectId,
        public string $to,
        public int $status,
    ) {}
}
