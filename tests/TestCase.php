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

    /** Strip all <script> blocks so substr_count assertions skip JSON-LD content. */
    protected function stripScripts(string $html): string
    {
        return preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    }

    /**
     * Drop the site footer, for assertions about what a page's own RELATIONS
     * put on it.
     *
     * The footer is site-wide navigation and lists every published service on
     * every page by design, so "this service is not related to this area" can
     * only be asserted against the page's own body. Without this, such a test
     * passes or fails on whatever the footer happens to carry rather than on
     * the relation it means to check.
     */
    protected function stripFooter(string $html): string
    {
        return preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $html);
    }
}
