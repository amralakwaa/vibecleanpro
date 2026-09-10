<?php

namespace Tests\Feature\Seo;

use App\Models\Redirect;
use App\Seo\RedirectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectResolverTest extends TestCase
{
    use RefreshDatabase;

    private RedirectResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(RedirectResolver::class);
    }

    public function test_resolves_an_exact_active_redirect(): void
    {
        Redirect::factory()->create(['from_path' => '/old-path', 'to_path' => '/new-path', 'type' => 301]);

        $resolution = $this->resolver->resolve('/old-path');

        $this->assertNotNull($resolution);
        $this->assertSame('/new-path', $resolution->to);
        $this->assertSame(301, $resolution->status);
    }

    public function test_returns_null_when_no_redirect_matches(): void
    {
        $this->assertNull($this->resolver->resolve('/nothing-here'));
    }

    public function test_an_inactive_redirect_is_never_resolved(): void
    {
        Redirect::factory()->create(['from_path' => '/inactive-path', 'to_path' => '/somewhere', 'is_active' => false]);

        $this->assertNull($this->resolver->resolve('/inactive-path'));
    }

    public function test_trailing_slashes_and_missing_leading_slash_are_normalized(): void
    {
        Redirect::factory()->create(['from_path' => '/old-path', 'to_path' => '/new-path']);

        $this->assertNotNull($this->resolver->resolve('old-path'));
        $this->assertNotNull($this->resolver->resolve('/old-path/'));
    }

    public function test_resolving_a_path_that_is_part_of_a_two_hop_chain_only_takes_one_hop(): void
    {
        // A chain like this can only exist if rows were inserted directly
        // (the admin form and RecordSlugChange both prevent it) - but the
        // resolver must still behave safely and deterministically if one
        // ever does: it does a single indexed lookup, never recursion, so
        // it returns the first hop rather than looping or erroring.
        Redirect::factory()->create(['from_path' => '/a', 'to_path' => '/b']);
        Redirect::factory()->create(['from_path' => '/b', 'to_path' => '/c']);

        $resolution = $this->resolver->resolve('/a');

        $this->assertSame('/b', $resolution->to);
    }

    public function test_recording_a_hit_increments_the_redirects_hit_counter(): void
    {
        $redirect = Redirect::factory()->create(['from_path' => '/old-path', 'to_path' => '/new-path', 'hits' => 0]);

        $this->resolver->recordHit($redirect->id);
        $this->resolver->recordHit($redirect->id);

        $this->assertSame(2, $redirect->fresh()->hits);
    }

    public function test_a_valid_external_https_destination_resolves_verbatim(): void
    {
        // Unsafe open-redirect destinations (javascript:, bare hostnames,
        // etc.) are rejected at write time by the admin form - see
        // RedirectFormGuardrailsTest - so the resolver only ever sees
        // already-validated destinations and can trust them as-is.
        Redirect::factory()->create(['from_path' => '/moved-away', 'to_path' => 'https://external-example.com/landing']);

        $resolution = $this->resolver->resolve('/moved-away');

        $this->assertSame('https://external-example.com/landing', $resolution->to);
    }
}
