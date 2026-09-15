<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Lead;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_page_renders(): void
    {
        $this->get('/contact')->assertOk();
    }

    public function test_only_real_business_profile_fields_are_shown_never_invented_ones(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('+966500000000');
        // No email/whatsapp/address were set - none of that markup exists.
        $response->assertDontSee('mailto:');
        $response->assertDontSee('wa.me', false);
    }

    public function test_a_valid_submission_creates_a_lead_with_the_contact_source(): void
    {
        $response = $this->post('/contact', [
            'name' => 'عبدالله',
            'phone' => '0500000001',
            'message' => 'استفسار عام.',
        ]);

        $response->assertRedirect(route('public.contact'));
        $this->assertDatabaseHas('leads', [
            'name' => 'عبدالله',
            'source' => 'contact_form',
        ]);
    }

    public function test_an_invalid_submission_is_rejected(): void
    {
        $response = $this->post('/contact', ['name' => '']);

        $response->assertSessionHasErrors(['name', 'phone']);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_a_filled_honeypot_creates_no_lead(): void
    {
        $this->post('/contact', ['name' => 'bot', 'phone' => '0500000002', 'website_url' => 'spam']);

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_the_business_context_keeps_its_framing_and_its_own_lead_source(): void
    {
        $page = $this->get('/contact?for=business')->assertOk()->getContent();

        $this->assertStringContainsString('حلول النظافة لمنشأتك', $page);
        $this->assertStringContainsString('name="context" value="business"', $page);
        // No competing "get a quote" pointer inside the B2B framing.
        $this->assertStringNotContainsString('تريد سعرًا لخدمة محددة؟', $page);

        $this->post('/contact', ['name' => 'منشأة', 'phone' => '0500000003', 'context' => 'business', 'message' => 'عقد تشغيل.']);

        $this->assertDatabaseHas('leads', ['name' => 'منشأة', 'source' => 'contact_form_business']);
        // A forged context value never invents a third source.
        $this->post('/contact', ['name' => 'مزور', 'phone' => '0500000004', 'context' => 'admin']);
        $this->assertDatabaseHas('leads', ['name' => 'مزور', 'source' => 'contact_form']);
    }

    public function test_price_intent_is_redirected_to_the_quote_page_in_one_line(): void
    {
        $page = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('تريد سعرًا لخدمة محددة؟', $page);
        $this->assertStringContainsString('href="'.route('public.quote').'"', $page);
    }

    public function test_channels_are_built_from_the_real_profile_and_carry_a_contact_message(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966 50 000 0000', 'whatsapp_number' => '+966500000000', 'email' => 'hi@example.test']);

        $page = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('href="tel:+966500000000"', $page);
        $this->assertStringContainsString('href="mailto:hi@example.test"', $page);
        $this->assertStringContainsString('https://wa.me/966500000000?text='.rawurlencode('مرحبًا، أرغب في التواصل معكم'), $page);
        // Nothing stored -> nothing shown: no hours, no address.
        $this->assertStringNotContainsString('ساعات العمل', $page);
        $this->assertStringNotContainsString('العنوان', $page);
    }

    public function test_a_validation_error_is_summarised_with_links_to_the_fields_and_keeps_the_input(): void
    {
        $response = $this->from('/contact')->post('/contact', ['name' => 'سلمى', 'phone' => 'abc']);

        $response->assertRedirect('/contact');
        $page = $this->get('/contact')->getContent();

        $this->assertStringContainsString('href="#phone"', $page);
        $this->assertStringContainsString('aria-invalid="true" aria-describedby="phone-error"', $page);
        $this->assertStringContainsString('value="سلمى"', $page);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_the_success_state_replaces_the_form_and_promises_no_response_time(): void
    {
        $this->post('/contact', ['name' => 'ريم', 'phone' => '0500000005']);
        $page = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('وصلتنا رسالتك', $page);
        $this->assertStringNotContainsString('action="'.route('public.contact.store').'"', $page);
        $this->assertDoesNotMatchRegularExpression('/خلال \d|دقائق|ساعة واحدة|24 ساعة|فورًا/u', $page);
    }

    public function test_the_sticky_mobile_bar_is_off_on_the_contact_page_but_still_on_elsewhere(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'whatsapp_number' => '+966500000000']);

        $this->assertStringNotContainsString('fixed inset-x-0 bottom-0', $this->get('/contact')->getContent());
        $this->assertStringContainsString('fixed inset-x-0 bottom-0', $this->get('/services')->getContent());
    }

    public function test_the_page_triggers_no_lazy_loading(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000', 'whatsapp_number' => '+966500000000', 'working_hours' => ['السبت' => '9-5']]);

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/contact')->assertOk();
            $this->get('/contact?for=business')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    public function test_the_privacy_link_appears_near_the_form_only_while_a_privacy_page_is_published(): void
    {
        $this->assertStringNotContainsString('سياسة الخصوصية', $this->get('/contact')->getContent());

        $page = Page::factory()->create(['type' => PageType::Legal, 'slug' => 'privacy', 'title' => 'سياسة الخصوصية']);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص السياسة كما يقره مالك الشركة.</p>']]);
        $this->assertStringNotContainsString('href="'.url('/privacy').'"', $this->get('/contact')->getContent());

        $page->update(['status' => PageStatus::Published]);
        $html = $this->get('/contact')->getContent();

        $this->assertStringContainsString('href="'.url('/privacy').'"', $html);
        // A quiet link, never a mandatory consent box.
        $this->assertDoesNotMatchRegularExpression('/type="checkbox"[^>]*(consent|privacy|agree)/u', $html);
    }
}
