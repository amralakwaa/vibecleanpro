<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * robots.txt only ever controls crawling, never indexing - a page we want
 * out of Google's index uses <meta name="robots" content="noindex"> (see
 * RobotsMetaRenderer) so the crawler can still reach it and see that tag.
 * Disallowing /admin here is defense in depth on top of real
 * authentication, not a substitute for it.
 *
 * config('app.url') already resolves correctly per environment (local vs
 * production), so the Sitemap line below never leaks a localhost URL once
 * this is actually deployed.
 */
class RobotsController extends Controller
{
    public function index(): Response
    {
        $sitemapUrl = rtrim(config('app.url'), '/').'/sitemap.xml';

        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /e',
            '',
            "Sitemap: {$sitemapUrl}",
        ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
