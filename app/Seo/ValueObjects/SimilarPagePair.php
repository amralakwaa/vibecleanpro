<?php

namespace App\Seo\ValueObjects;

use App\Models\Page;

final readonly class SimilarPagePair
{
    public function __construct(
        public Page $pageA,
        public Page $pageB,
        public float $score,
    ) {}
}
