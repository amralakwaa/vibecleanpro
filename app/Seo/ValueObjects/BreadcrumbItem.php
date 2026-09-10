<?php

namespace App\Seo\ValueObjects;

final readonly class BreadcrumbItem
{
    public function __construct(
        public string $label,
        public ?string $url,
    ) {}
}
