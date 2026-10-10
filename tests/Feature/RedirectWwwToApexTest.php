<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The apex redirect is the only thing stopping the whole site from
 * answering 200 on two hostnames, so each of its edges is pinned here:
 * it must fire on www, keep the path and query, leave the apex alone,
 * and never fire when APP_URL is itself a www host (which would loop).
 */
class RedirectWwwToApexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://vibecleanpro.com']);
    }

    public function test_it_redirects_the_www_host_to_the_apex_permanently(): void
    {
        $response = $this->get('https://www.vibecleanpro.com/');

        $response->assertStatus(301);
        $response->assertRedirect('https://vibecleanpro.com/');
    }

    public function test_it_keeps_the_path_and_query_string(): void
    {
        $response = $this->get('https://www.vibecleanpro.com/services/sofa-cleaning?utm_source=google');

        $response->assertStatus(301);
        $response->assertRedirect('https://vibecleanpro.com/services/sofa-cleaning?utm_source=google');
    }

    public function test_it_leaves_the_apex_host_alone(): void
    {
        $this->get('https://vibecleanpro.com/')->assertStatus(200);
    }

    public function test_it_does_not_redirect_an_unrelated_host(): void
    {
        $this->get('https://staging.vibecleanpro.com/')->assertStatus(200);
    }

    public function test_it_never_redirects_when_the_configured_host_is_itself_www(): void
    {
        config(['app.url' => 'https://www.vibecleanpro.com']);

        $this->get('https://www.vibecleanpro.com/')->assertStatus(200);
    }
}
