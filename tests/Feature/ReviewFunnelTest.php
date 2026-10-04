<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The honest Google review funnel: the site asks past customers for a real
 * review (the strongest local ranking signal) only when a real review link
 * is configured, never suggesting a rating or filtering who is asked.
 */
class ReviewFunnelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_shows_the_review_cta_only_when_a_real_review_link_exists(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'name_ar' => 'فايب كلين برو', 'city' => 'الرياض']);

        $this->get('/')->assertOk()->assertDontSee('قيّم تجربتك معنا على Google');

        BusinessProfile::query()->first()->update([
            'google_review_url' => 'https://g.page/r/vibecleanpro/review',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('قيّم تجربتك معنا على Google')
            ->assertSee('https://g.page/r/vibecleanpro/review', escape: false);
    }

    public function test_the_footer_links_the_google_business_profile_only_when_it_is_real(): void
    {
        $profile = BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'name_ar' => 'فايب كلين برو', 'city' => 'الرياض']);

        $this->get('/')->assertOk()->assertDontSee('خرائط Google');

        $profile->update(['google_business_profile_url' => 'https://maps.google.com/?cid=12345']);

        $this->get('/')
            ->assertOk()
            ->assertSee('خرائط Google')
            ->assertSee('https://maps.google.com/?cid=12345', escape: false);
    }
}
