<?php

namespace App\Seo\ValueObjects;

use App\Models\InternalLink;

final readonly class BrokenLink
{
    public function __construct(
        public InternalLink $link,
        public string $reason,
    ) {}
}
