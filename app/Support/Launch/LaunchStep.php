<?php

namespace App\Support\Launch;

final readonly class LaunchStep
{
    public function __construct(
        public string $section,
        public string $target,
        public string $action,
        public bool $changes,
    ) {}
}
