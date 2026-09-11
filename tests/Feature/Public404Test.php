<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Public404Test extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_url_returns_a_real_http_404(): void
    {
        $this->get('/this-page-does-not-exist-at-all')->assertNotFound();
    }

    public function test_the_404_page_uses_the_site_identity_and_offers_real_navigation(): void
    {
        $response = $this->get('/this-page-does-not-exist-at-all');

        $response->assertNotFound();
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('#<html[^>]*lang="ar"[^>]*dir="rtl"#', $html);
        $this->assertStringContainsString('href="'.route('public.services.index').'"', $html);
        $this->assertStringContainsString('href="'.route('public.contact').'"', $html);
    }

    public function test_the_404_page_is_noindex(): void
    {
        $response = $this->get('/this-page-does-not-exist-at-all');

        $response->assertSee('noindex', false);
    }

    public function test_a_missing_page_never_silently_redirects_to_the_homepage(): void
    {
        $response = $this->get('/this-page-does-not-exist-at-all');

        $response->assertNotFound();
        $response->assertHeaderMissing('Location');
    }
}
