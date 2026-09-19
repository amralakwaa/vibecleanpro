<?php

namespace Tests;

use App\Jobs\GenerateMediaVariants;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Bus;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Factory media often points at the real stock library on disk; with
        // the sync queue every such row would be decoded and resized. Only
        // this one job is faked - ResponsiveImagesTest runs it explicitly.
        Bus::fake([GenerateMediaVariants::class]);
    }
}
