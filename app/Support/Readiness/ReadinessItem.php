<?php

namespace App\Support\Readiness;

/**
 * One integration's real state, with the reason and where to fix it.
 * Never carries a secret value - only whether one is stored.
 */
final readonly class ReadinessItem
{
    public function __construct(
        public string $key,
        public string $title,
        public string $state,
        public string $label,
        public string $detail,
        public ?string $url = null,
        public ?string $actionLabel = null,
    ) {}

    public function isGood(): bool
    {
        return in_array($this->state, ['ready', 'active', 'configured', 'published', 'complete'], true);
    }

    public function color(): string
    {
        return match (true) {
            $this->isGood() => 'success',
            in_array($this->state, ['configured_disabled', 'draft', 'not_configured'], true) => 'gray',
            default => 'danger',
        };
    }
}
