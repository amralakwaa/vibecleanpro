<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_the_quote_form_renders(): void
    {
        $this->get('/quote')->assertOk();
    }

    public function test_a_valid_submission_creates_a_lead_with_the_correct_source(): void
    {
        $response = $this->post('/quote', [
            'name' => 'أحمد',
            'phone' => '0500000000',
            'message' => 'أحتاج تنظيف سجاد.',
        ]);

        $response->assertRedirect(route('public.quote'));
        $this->assertDatabaseHas('leads', [
            'name' => 'أحمد',
            'phone' => '0500000000',
            'source' => 'quote_form',
        ]);
    }

    public function test_a_published_service_and_area_are_accepted_and_stored_on_the_lead(): void
    {
        $service = $this->createCompliantServicePage(slug: 'published-svc')->pageable;
        $area = $this->createCompliantAreaPage(slug: 'published-area')->pageable;

        $this->post('/quote', [
            'name' => 'سارة',
            'phone' => '0511111111',
            'service_id' => $service->id,
            'area_id' => $area->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leads', [
            'name' => 'سارة',
            'service_id' => $service->id,
            'area_id' => $area->id,
        ]);
    }

    /**
     * Public publication semantics apply to the POST exactly as they do
     * to the select options: a tampered request naming a Service the
     * public cannot see fails validation and never becomes a lead.
     */
    public function test_an_unpublished_service_is_rejected_and_no_lead_is_created(): void
    {
        $draft = $this->createCompliantServicePage(slug: 'draft-svc', status: PageStatus::Draft)->pageable;
        $review = $this->createCompliantServicePage(slug: 'review-svc', status: PageStatus::Review)->pageable;
        $pageless = Service::factory()->create();

        foreach ([$draft, $review, $pageless] as $service) {
            $this->post('/quote', ['name' => 'متلاعب', 'phone' => '0511111112', 'service_id' => $service->id])
                ->assertSessionHasErrors(['service_id' => 'الخدمة المختارة غير متاحة حاليًا.']);
        }

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_an_unpublished_area_is_rejected_and_no_lead_is_created(): void
    {
        $draft = $this->createCompliantAreaPage(slug: 'draft-area', status: PageStatus::Draft)->pageable;
        $pageless = Area::factory()->create();

        foreach ([$draft, $pageless] as $area) {
            $this->post('/quote', ['name' => 'متلاعب', 'phone' => '0511111113', 'area_id' => $area->id])
                ->assertSessionHasErrors(['area_id' => 'المنطقة المختارة غير متاحة حاليًا.']);
        }

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_a_soft_deleted_service_is_rejected_even_though_its_row_still_exists(): void
    {
        $service = $this->createCompliantServicePage(slug: 'deleted-svc')->pageable;
        $service->delete();

        $this->post('/quote', ['name' => 'متلاعب', 'phone' => '0511111114', 'service_id' => $service->id])
            ->assertSessionHasErrors('service_id');

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_the_contact_form_applies_the_same_publication_rule(): void
    {
        $pageless = Service::factory()->create();

        $this->post('/contact', ['name' => 'متلاعب', 'phone' => '0511111115', 'service_id' => $pageless->id])
            ->assertSessionHasErrors('service_id');

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_an_invalid_submission_is_rejected_and_creates_no_lead(): void
    {
        $response = $this->post('/quote', ['name' => '', 'phone' => '']);

        $response->assertSessionHasErrors(['name', 'phone']);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_a_service_id_that_does_not_exist_is_rejected(): void
    {
        $response = $this->post('/quote', ['name' => 'خالد', 'phone' => '0522222222', 'service_id' => 999999]);

        $response->assertSessionHasErrors('service_id');
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_utm_parameters_captured_earlier_in_the_session_are_preserved_on_submission(): void
    {
        // Simulates a visitor landing on a UTM-tagged page, then
        // navigating to /quote before submitting - see
        // CaptureLeadAttribution.
        $this->get('/?utm_source=google&utm_medium=cpc&utm_campaign=summer');

        $this->post('/quote', ['name' => 'منى', 'phone' => '0533333333']);

        $this->assertDatabaseHas('leads', [
            'name' => 'منى',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer',
            'landing_page' => '/',
        ]);
    }

    public function test_a_filled_honeypot_field_silently_pretends_success_without_creating_a_lead(): void
    {
        $response = $this->post('/quote', [
            'name' => 'bot',
            'phone' => '0544444444',
            'website_url' => 'http://spam.example',
        ]);

        $response->assertRedirect(route('public.quote'));
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_the_success_state_is_shown_after_a_valid_submission_with_whatsapp_and_call_ctas(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000', 'phone' => '+966500000000']);

        $this->post('/quote', ['name' => 'فهد', 'phone' => '0555555555']);
        $response = $this->get('/quote');

        $response->assertOk();
        $response->assertSee('تم استلام طلبك بنجاح');
        $response->assertSee('wa.me', false);
    }

    public function test_mass_assignment_cannot_set_a_leads_status_or_id_from_the_public_form(): void
    {
        $this->post('/quote', [
            'name' => 'مهاجم',
            'phone' => '0566666666',
            'status' => 'won',
            'id' => 999,
        ]);

        $lead = Lead::query()->where('name', 'مهاجم')->firstOrFail();

        $this->assertSame('new', $lead->status->value);
        $this->assertNotSame(999, $lead->id);
    }

    public function test_the_submit_route_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/quote', ['name' => "user-{$i}", 'phone' => '0500000000'])->assertRedirect();
        }

        $this->post('/quote', ['name' => 'one-too-many', 'phone' => '0500000000'])
            ->assertStatus(429);
    }

    public function test_service_and_area_prefill_is_selected_and_stated_in_words(): void
    {
        $service = $this->createCompliantServicePage(slug: 'prefill-svc')->pageable;
        $service->update(['name' => 'خدمة-محددة-مسبقًا']);
        $area = $this->createCompliantAreaPage(slug: 'prefill-area')->pageable;
        $area->update(['name' => 'منطقة-محددة-مسبقًا']);

        // Services and areas have independent ids, so each check is scoped
        // to its own <select> rather than to the whole page.
        $select = fn (string $html, string $name): string => preg_match('/<select[^>]*name="'.$name.'"[\s\S]*?<\/select>/u', $html, $m) ? $m[0] : '';

        $both = $this->get('/quote?service='.$service->id.'&area='.$area->id)->assertOk()->getContent();
        $this->assertStringContainsString('<option value="'.$service->id.'" selected', $select($both, 'service_id'));
        $this->assertStringContainsString('<option value="'.$area->id.'" selected', $select($both, 'area_id'));
        $this->assertStringContainsString('تطلب:', $both);
        $this->assertStringContainsString('في منطقة-محددة-مسبقًا', $both);

        $serviceOnly = $this->get('/quote?service='.$service->id)->assertOk()->getContent();
        $this->assertStringContainsString('<option value="'.$service->id.'" selected', $select($serviceOnly, 'service_id'));
        $this->assertStringNotContainsString('<option value="'.$area->id.'" selected', $select($serviceOnly, 'area_id'));
        $this->assertStringNotContainsString('في منطقة-محددة-مسبقًا', $serviceOnly);

        $this->assertStringNotContainsString('تطلب:', $this->get('/quote')->getContent());
    }

    public function test_unknown_or_unpublished_prefill_references_are_ignored_safely(): void
    {
        $draft = $this->createCompliantServicePage(slug: 'draft-svc', status: PageStatus::Draft)->pageable;
        $draft->update(['name' => 'خدمة-مسودة-لا-تظهر']);

        foreach (['/quote?service=999999&area=999999', '/quote?service='.$draft->id, '/quote?service=abc'] as $url) {
            $page = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('تطلب:', $page);
            $this->assertStringNotContainsString('خدمة-مسودة-لا-تظهر', $page);
            $this->assertDoesNotMatchRegularExpression('/<option value="\d+" selected/u', $page);
        }
    }

    public function test_the_whatsapp_path_carries_the_real_prefill_and_stays_generic_without_it(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);
        $service = $this->createCompliantServicePage(slug: 'wa-svc')->pageable;
        $service->update(['name' => 'تنظيف-خزانات-واتساب']);

        $prefilled = $this->get('/quote?service='.$service->id)->getContent();
        $this->assertStringContainsString('wa.me/966500000000?text='.rawurlencode('مرحبًا، أرغب في طلب عرض سعر لخدمة تنظيف-خزانات-واتساب'), $prefilled);

        $plain = $this->get('/quote')->getContent();
        $this->assertStringContainsString('wa.me/966500000000?text='.rawurlencode('مرحبًا، أرغب في طلب عرض سعر'), $plain);
        $this->assertStringNotContainsString(rawurlencode('لخدمة'), $plain);
    }

    public function test_a_validation_error_keeps_the_prefill_and_links_to_the_failing_field(): void
    {
        $service = $this->createCompliantServicePage(slug: 'keep-svc')->pageable;

        $this->from('/quote?service='.$service->id)
            ->post('/quote', ['name' => 'نورة', 'phone' => 'abc', 'service_id' => $service->id])
            ->assertRedirect('/quote?service='.$service->id);

        $page = $this->get('/quote?service='.$service->id)->getContent();

        $this->assertStringContainsString('href="#phone"', $page);
        $this->assertStringContainsString('aria-invalid="true" aria-describedby="phone-error"', $page);
        $this->assertStringContainsString('<option value="'.$service->id.'" selected', $page);
        $this->assertStringContainsString('value="نورة"', $page);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_the_success_state_replaces_the_form_and_promises_no_response_time(): void
    {
        $this->post('/quote', ['name' => 'هند', 'phone' => '0577777777']);
        $page = $this->get('/quote')->assertOk()->getContent();

        $this->assertStringContainsString('وصلنا طلبك', $page);
        $this->assertStringNotContainsString('action="'.route('public.quote.store').'"', $page);
        $this->assertSame(1, substr_count($page, '<h1'));
        $this->assertDoesNotMatchRegularExpression('/خلال \d|دقائق|ساعة واحدة|24 ساعة|فورًا|ضمان/u', $page);
    }

    public function test_the_form_makes_no_unbacked_claims_and_has_no_competing_sticky_cta(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);

        $page = $this->get('/quote')->assertOk()->getContent();

        $this->assertStringContainsString('لا يوجد دفع عبر الموقع', $page);
        $this->assertDoesNotMatchRegularExpression('/خلال \d|دقائق|ضمان|عملاء راضون|\d+ عميل/u', $page);
        $this->assertStringNotContainsString('fixed inset-x-0 bottom-0', $page);
    }

    public function test_the_page_triggers_no_lazy_loading(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000', 'whatsapp_number' => '+966500000000']);
        $service = $this->createCompliantServicePage(slug: 'lazy-svc')->pageable;
        $area = $this->createCompliantAreaPage(slug: 'lazy-area')->pageable;

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/quote')->assertOk();
            $this->get('/quote?service='.$service->id.'&area='.$area->id)->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    public function test_the_privacy_link_appears_near_the_submit_only_while_a_privacy_page_is_published(): void
    {
        $this->assertStringNotContainsString('سياسة الخصوصية', $this->get('/quote')->getContent());

        $page = Page::factory()->create(['type' => PageType::Legal, 'slug' => 'privacy', 'title' => 'سياسة الخصوصية']);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص السياسة.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        $html = $this->get('/quote')->getContent();
        $this->assertStringContainsString('href="'.url('/privacy').'"', $html);
        $this->assertLessThan(mb_strpos($html, 'href="'.url('/privacy').'"'), mb_strpos($html, 'إرسال الطلب'));
        $this->assertDoesNotMatchRegularExpression('/type="checkbox"[^>]*(consent|privacy|agree)/u', $html);

        // A Trust page at the same slug is not the privacy policy.
        $page->update(['type' => PageType::Trust]);
        $this->assertStringNotContainsString('href="'.url('/privacy').'"', $this->get('/quote')->getContent());
    }
}
