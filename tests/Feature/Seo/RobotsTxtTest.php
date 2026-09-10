<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_disallows_the_admin_path(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Disallow: /admin', false);
    }

    public function test_robots_txt_points_at_the_sitemap_using_the_configured_app_url(): void
    {
        $response = $this->get('/robots.txt');

        $expected = 'Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml';

        $response->assertSee($expected, false);
    }

    public function test_robots_txt_never_disallows_the_whole_site(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertDontSee('Disallow: /'."\n", false);
        $response->assertSee('User-agent: *', false);
    }
}
