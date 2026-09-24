<?php

namespace Tests\Feature;

use App\Enums\ConversionEventType;
use App\Enums\PageStatus;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Support\Tracking\AttributionCode;
use App\Support\Tracking\VisitorClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class ConversionTrackingTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();

        // A real browser sends its session cookie on every request; the test
        // client does not, so pin one to make "the same visitor" real.
        $this->withCookie(config('session.cookie'), Str::random(40));
    }

    private function beacon(array $data, string $userAgent = self::BROWSER): TestResponse
    {
        return $this->withHeader('User-Agent', $userAgent)->post('/e', $data);
    }

    public function test_a_whatsapp_click_on_a_service_page_is_stored_with_server_resolved_context(): void
    {
        $page = $this->createCompliantServicePage(slug: 'villa-cleaning');

        $this->beacon(['type' => 'whatsapp_click', 'path' => '/services/villa-cleaning', 'service_id' => 999])
            ->assertNoContent();

        $event = ConversionEvent::query()->sole();
        $this->assertSame(ConversionEventType::WhatsappClick, $event->event_type);
        $this->assertSame($page->id, $event->page_id);
        $this->assertSame($page->pageable_id, $event->service_id);
        $this->assertSame('/services/villa-cleaning', $event->page_path);
        $this->assertSame('mobile', $event->device_type);
        $this->assertSame('direct', $event->source);
    }

    public function test_a_phone_click_on_an_area_page_resolves_the_area(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'al-sahafa');

        $this->beacon(['type' => 'phone_click', 'path' => '/areas/al-sahafa/'])->assertNoContent();

        $event = ConversionEvent::query()->sole();
        $this->assertSame($page->pageable_id, $event->area_id);
        $this->assertNull($event->service_id);
        $this->assertSame('/areas/al-sahafa', $event->page_path);
    }

    public function test_a_draft_page_or_a_slug_under_the_wrong_prefix_gets_no_page_context(): void
    {
        $this->createCompliantServicePage(slug: 'draft-service', status: PageStatus::Draft);
        $this->createCompliantServicePage(slug: 'live-service');

        $this->beacon(['type' => 'whatsapp_click', 'path' => '/services/draft-service']);
        $this->beacon(['type' => 'whatsapp_click', 'path' => '/areas/live-service']);

        $this->assertSame(2, ConversionEvent::query()->whereNull('page_id')->whereNull('service_id')->count());
    }

    public function test_the_browser_cannot_report_submissions_or_uploads(): void
    {
        foreach (['quote_form_submit', 'contact_form_submit', 'file_upload', 'made_up'] as $type) {
            $this->beacon(['type' => $type, 'path' => '/'])->assertNoContent();
        }

        $this->assertSame(0, ConversionEvent::query()->count());
    }

    public function test_invalid_paths_are_ignored_silently(): void
    {
        $this->beacon(['type' => 'phone_click', 'path' => 'https://evil.example/'])->assertNoContent();
        $this->beacon(['type' => 'phone_click'])->assertNoContent();

        $this->assertSame(0, ConversionEvent::query()->count());
    }

    public function test_bots_are_not_recorded(): void
    {
        $this->beacon(['type' => 'whatsapp_click', 'path' => '/'], 'Mozilla/5.0 (compatible; Googlebot/2.1)')->assertNoContent();
        $this->beacon(['type' => 'whatsapp_click', 'path' => '/'], '')->assertNoContent();

        $this->assertSame(0, ConversionEvent::query()->count());
    }

    public function test_a_repeated_click_within_the_dedupe_window_counts_once(): void
    {
        $this->beacon(['type' => 'whatsapp_click', 'path' => '/']);
        $this->beacon(['type' => 'whatsapp_click', 'path' => '/']);
        $this->beacon(['type' => 'phone_click', 'path' => '/']);

        $this->assertSame(1, ConversionEvent::query()->where('event_type', 'whatsapp_click')->count());
        $this->assertSame(1, ConversionEvent::query()->where('event_type', 'phone_click')->count());
    }

    public function test_the_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->beacon(['type' => 'phone_click', 'path' => '/p-'.$i]);
        }

        $this->beacon(['type' => 'phone_click', 'path' => '/one-more'])->assertStatus(429);
    }

    public function test_the_first_touch_source_comes_from_the_session_attribution(): void
    {
        $this->withHeader('Referer', 'https://www.google.com/')->get('/quote')->assertOk();

        // The beacon itself carries only a same-site referer, as in a browser.
        $this->flushHeaders();
        $this->withHeader('Referer', 'http://localhost/quote');
        $this->beacon(['type' => 'quote_form_start', 'path' => '/quote']);

        $this->assertSame('organic_search', ConversionEvent::query()->sole()->source);
    }

    public function test_a_quote_submission_is_recorded_by_the_server_with_the_lead(): void
    {
        $service = $this->createCompliantServicePage(slug: 'sofa-cleaning')->pageable;

        $this->withHeader('User-Agent', self::BROWSER)->post('/quote', [
            'name' => 'سارة',
            'phone' => '0511111111',
            'service_id' => $service->id,
        ])->assertSessionHasNoErrors();

        $lead = Lead::query()->sole();
        $event = ConversionEvent::query()->sole();
        $this->assertSame(ConversionEventType::QuoteFormSubmit, $event->event_type);
        $this->assertSame($lead->id, $event->lead_id);
        $this->assertSame($service->id, $event->service_id);
    }

    public function test_a_contact_submission_is_recorded(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)->post('/contact', [
            'name' => 'خالد',
            'phone' => '0522222222',
            'message' => 'استفسار',
        ])->assertSessionHasNoErrors();

        $this->assertSame(ConversionEventType::ContactFormSubmit, ConversionEvent::query()->sole()->event_type);
    }

    public function test_a_honeypot_submission_records_nothing(): void
    {
        $this->post('/quote', ['name' => 'bot', 'phone' => '0500000000', 'website_url' => 'http://spam.test']);

        $this->assertSame(0, ConversionEvent::query()->count());
    }

    public function test_the_attribution_code_round_trips_to_its_published_page(): void
    {
        $page = $this->createCompliantServicePage(slug: 'villa-cleaning');
        $code = AttributionCode::forPath('/services/villa-cleaning/');

        $this->assertMatchesRegularExpression('/^V-[0-9A-Z]{7}$/', $code);
        $this->assertSame($code, AttributionCode::forPath('/services/villa-cleaning'));
        $this->assertTrue($page->is(app(AttributionCode::class)->resolve(strtolower(substr($code, 2)))));
        $this->assertNull(app(AttributionCode::class)->resolve('V-0000000'));
    }

    public function test_the_layout_exposes_the_tracking_endpoint_and_a_csrf_token(): void
    {
        $this->get('/quote')
            ->assertSee('<meta name="vcp-track" content="'.route('public.track').'">', false)
            ->assertSee('name="csrf-token"', false)
            ->assertSee('data-track-form="quote_form_start"', false);
    }

    public function test_robots_disallows_the_tracking_endpoint(): void
    {
        $this->get('/robots.txt')->assertSee('Disallow: /e', false);
    }

    public function test_visitor_classification(): void
    {
        $visitors = new VisitorClassifier;

        $this->assertSame('tablet', $visitors->deviceType('Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)'));
        $this->assertSame('desktop', $visitors->deviceType('Mozilla/5.0 (Windows NT 10.0; Win64; x64)'));
        $this->assertSame('gbp', $visitors->source('google', 'organic', 'gbp', null));
        $this->assertSame('paid', $visitors->source('google', 'cpc', 'summer', null));
        $this->assertSame('social', $visitors->source('instagram', 'bio', null, null));
        $this->assertSame('social', $visitors->source(null, null, null, 'l.instagram.com'));
        $this->assertSame('referral', $visitors->source(null, null, null, 'example.sa'));
    }
}
