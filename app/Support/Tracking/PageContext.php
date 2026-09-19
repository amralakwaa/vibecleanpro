<?php

namespace App\Support\Tracking;

/**
 * Which page, service and area a public URL belongs to - resolved on the
 * server from the path, never taken from the browser.
 */
final readonly class PageContext
{
    public function __construct(
        public ?int $pageId = null,
        public ?int $serviceId = null,
        public ?int $areaId = null,
    ) {}
}
