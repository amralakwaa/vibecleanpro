<?php

namespace Tests\Feature\Seo;

use App\Seo\CanonicalResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class CanonicalResolverTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private CanonicalResolver $canonical;

    protected function setUp(): void
    {
        parent::setUp();

        $this->canonical = app(CanonicalResolver::class);
    }

    public function test_default_canonical_is_the_pages_own_absolute_url(): void
    {
        $page = $this->createCompliantServicePage(slug: 'window-cleaning');

        $expected = rtrim(config('app.url'), '/').'/services/window-cleaning';

        $this->assertSame($expected, $this->canonical->resolve($page));
    }

    public function test_a_valid_custom_canonical_is_used_instead_of_the_default(): void
    {
        $page = $this->createCompliantServicePage(slug: 'sofa-cleaning');
        $page->seoMetadata->update(['canonical_url' => rtrim(config('app.url'), '/').'/services/sofa-cleaning-riyadh']);

        $this->assertSame(
            rtrim(config('app.url'), '/').'/services/sofa-cleaning-riyadh',
            $this->canonical->resolve($page->fresh('seoMetadata')),
        );
    }

    public function test_an_invalid_custom_canonical_falls_back_to_the_self_referencing_default(): void
    {
        $page = $this->createCompliantServicePage(slug: 'mattress-cleaning');
        // Bypass the form-level URL validation to prove the resolver itself
        // is the safety net, not just the admin form.
        $page->seoMetadata->forceFill(['canonical_url' => 'javascript:alert(1)'])->save();

        $expected = rtrim(config('app.url'), '/').'/services/mattress-cleaning';

        $this->assertSame($expected, $this->canonical->resolve($page->fresh('seoMetadata')));
    }

    public function test_is_valid_rejects_javascript_pseudo_protocol(): void
    {
        $this->assertFalse($this->canonical->isValid('javascript:alert(1)'));
    }

    public function test_is_valid_rejects_an_empty_or_blank_value(): void
    {
        $this->assertFalse($this->canonical->isValid(''));
        $this->assertFalse($this->canonical->isValid('   '));
    }

    public function test_is_valid_rejects_a_value_with_no_host_or_scheme(): void
    {
        $this->assertFalse($this->canonical->isValid('/just-a-path'));
        $this->assertFalse($this->canonical->isValid('not a url at all'));
    }

    public function test_is_valid_accepts_a_well_formed_http_or_https_url(): void
    {
        $this->assertTrue($this->canonical->isValid('https://example.com/page'));
        $this->assertTrue($this->canonical->isValid('http://example.com/page'));
    }

    public function test_is_valid_rejects_non_http_schemes(): void
    {
        $this->assertFalse($this->canonical->isValid('ftp://example.com/file'));
    }

    public function test_query_parameters_never_change_the_resolved_canonical(): void
    {
        $page = $this->createCompliantServicePage(slug: 'upholstery-cleaning');

        $expected = rtrim(config('app.url'), '/').'/services/upholstery-cleaning';

        // The resolver never consults the current request at all, so it is
        // by construction blind to any query string (UTM or otherwise).
        $this->assertSame($expected, $this->canonical->resolve($page));
    }

    public function test_a_request_carrying_utm_parameters_still_renders_the_clean_self_referencing_canonical(): void
    {
        $page = $this->createCompliantServicePage(slug: 'curtain-cleaning');

        $response = $this->get('/services/curtain-cleaning?utm_source=facebook&utm_medium=social&utm_campaign=summer');

        $response->assertOk();
        $expected = rtrim(config('app.url'), '/').'/services/curtain-cleaning';
        $response->assertSee('<link rel="canonical" href="'.$expected.'">', false);
        $response->assertDontSee('utm_source', false);
    }
}
