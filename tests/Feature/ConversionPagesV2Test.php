<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AssertsNoEmptyState;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Phase 5 contracts for the conversion and trust pages: the quote form
 * shows service context only through PublicPrice and says plainly that it
 * is a request and not a paid booking, contact and quote share one form
 * system with focusable error summaries, the About page never puts a
 * stock face or a stock crew on the page and falls back to a monogram of
 * the stored name, and a legal page shows only the editor's wording on the
 * shared Trust Center reading surface, inventing no policy content of its own.
 */
class ConversionPagesV2Test extends TestCase
{
    use AssertsNoEmptyState, BuildsSeoFixtures, RefreshDatabase;

    public function test_the_quote_context_prices_only_through_public_price_and_names_the_request_honestly(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000', 'whatsapp_number' => '+966500000000']);
        $priced = $this->createCompliantServicePage(slug: 'q-priced')->pageable;
        $priced->update(['name' => 'خدمة-بسعر-معلن', 'pricing_mode' => 'starting_from', 'price_min' => 450]);
        $quoteOnly = $this->createCompliantServicePage(slug: 'q-quote')->pageable;
        $quoteOnly->update(['name' => 'خدمة-عرض-فقط', 'pricing_mode' => 'quote_only']);

        $pricedHtml = $this->get('/quote?service='.$priced->id)->assertOk()->getContent();
        $quoteOnlyHtml = $this->get('/quote?service='.$quoteOnly->id)->assertOk()->getContent();
        $plainHtml = $this->get('/quote')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/تطلب:[\s\S]{0,1200}خدمة-بسعر-معلن[\s\S]{0,200}يبدأ من 450 ر\.س/u', $pricedHtml, 'a real public price is stated as context');
        $this->assertStringContainsString('الخدمة المختارة', $pricedHtml);
        $this->assertStringContainsString('خدمة-عرض-فقط', $quoteOnlyHtml);
        $quoteOnlyMain = mb_substr($quoteOnlyHtml, mb_strpos($quoteOnlyHtml, '<main'), mb_strpos($quoteOnlyHtml, '</main>') - mb_strpos($quoteOnlyHtml, '<main'));
        $this->assertDoesNotMatchRegularExpression('/\d+\s*ر\.س|يبدأ من|السعر حسب الطلب/u', $quoteOnlyMain, 'quote-only context carries no number');
        $this->assertDoesNotMatchRegularExpression('/تطلب:[\s\S]{0,1500}اطلب عرض سعر/u', $quoteOnlyMain, 'and no stand-in label beside the service');

        foreach ([$pricedHtml, $quoteOnlyHtml, $plainHtml] as $html) {
            $this->assertStringContainsString('لا يوجد دفع عبر الموقع — هذا طلب عرض سعر وليس حجزًا مدفوعًا.', $html);
            $this->assertSame(3, preg_match_all('/<legend[\s\S]{0,400}?>0[123]<\/span>/u', $html), 'three numbered groups, no wizard');
            $this->assertStringNotContainsString('fixed inset-x-0 bottom-0', $html, 'nothing sticky competes with the form');
            $this->assertDoesNotMatchRegularExpression('/خلال \d|دقائق|ساعة واحدة|24 ساعة|فورًا|ضمان|عملاء راضون/u', $html);
            $this->assertSame(1, substr_count($html, '<h1'));
        }
    }

    public function test_contact_and_quote_share_one_form_system_with_a_focusable_error_summary(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);

        // Flashed errors are read by the request right after the POST.
        $this->from('/contact')->post('/contact', ['name' => 'سلمى', 'phone' => 'x'])->assertRedirect('/contact');
        $errored = $this->get('/contact')->getContent();
        $this->assertMatchesRegularExpression('/<div role="alert" tabindex="-1"[^>]*x-init="\$nextTick\(\(\) => \$el\.focus\(\)\)"/u', $errored, 'the summary receives focus');
        $this->assertStringContainsString('href="#phone"', $errored);
        $this->assertStringContainsString('aria-invalid="true" aria-describedby="phone-error"', $errored);
        $this->assertStringContainsString('id="phone-error"', $errored);

        $contact = $this->get('/contact')->assertOk()->getContent();
        $business = $this->get('/contact?for=business')->assertOk()->getContent();
        $quote = $this->get('/quote')->assertOk()->getContent();

        foreach ([$contact, $quote] as $html) {
            $this->assertStringContainsString('surface-tint', $html);
            $this->assertStringContainsString('shadow-xl shadow-primary-900/10', $html, 'the same framed form panel');
            $this->assertStringContainsString('مطلوبة.', $html, 'required fields are explained, never guessed from placeholders');
            $this->assertStringNotContainsString('placeholder=', $html);
            $this->assertStringNotContainsString('surface-atmos', $html, 'a form page never opens on the navy band');
        }

        $this->assertStringContainsString('تواصل بخصوص منشأتك', $contact, 'the business path is offered beside the channels');
        $this->assertStringNotContainsString('تواصل بخصوص منشأتك', $business, 'and not to a visitor already on it');
    }

    public function test_the_about_page_leads_with_the_brand_and_never_a_stock_face(): void
    {
        $stock = Media::factory()->create(['path' => Media::LIBRARY_DIRECTORY.'/crew.jpg']);
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'tagline' => 'وصف-مختصر',
            'identity_statement' => 'بيان-الهوية',
            'city' => 'الرياض',
            'mission' => 'رسالة-من-الإدارة',
            'vision' => 'رؤية-من-الإدارة',
            'founder_name' => 'عمر-بلا-صورة',
            'founder_title' => 'المدير التنفيذي',
            'logo_media_id' => $stock->id,
        ]);
        TeamMember::factory()->create(['name' => 'سارة-بلا-صورة', 'photo_media_id' => null, 'sort_order' => 1]);
        TeamMember::factory()->create(['name' => 'أحمد-بلا-صورة', 'photo_media_id' => null, 'sort_order' => 2]);
        Page::factory()->create(['type' => PageType::About, 'slug' => 'about', 'title' => 'من نحن', 'status' => PageStatus::Published]);

        $html = $this->get('/about')->assertOk()->getContent();
        $main = mb_substr($html, mb_strpos($html, '<main'), mb_strpos($html, '</main>') - mb_strpos($html, '<main'));

        $this->assertMatchesRegularExpression('/<section class="surface-atmos[^"]*">[\s\S]{0,2500}<h1/u', $html, 'the opening is the brand field');
        $this->assertStringNotContainsString('<img', $main, 'no portrait and no stock crew stands in for the company');
        $this->assertStringNotContainsString(Media::LIBRARY_DIRECTORY, $main);
        $this->assertMatchesRegularExpression('/<div class="surface-atmos[^"]*">[\s\S]{0,300}<h3[^>]*>عمر-بلا-صورة<\/h3>/u', $html, 'the founder name is set as type on the brand field');
        $this->assertSame(1, substr_count($html, 'عمر-بلا-صورة</h3>'), 'the name is not repeated beside its own plate');
        $this->assertMatchesRegularExpression('/<span[^>]*aria-hidden="true">س<\/span>[\s\S]{0,300}<h3[^>]*>سارة-بلا-صورة<\/h3>/u', $html, 'a team member without a photo gets a monogram');
        $this->assertMatchesRegularExpression('/<span[^>]*aria-hidden="true">أح<\/span>[\s\S]{0,300}<h3[^>]*>أحمد-بلا-صورة<\/h3>/u', $html, 'an alef-initial name takes two letters, never a lone stroke');
        $this->assertStringContainsString('مقر العمل</dt>', $html);
        $this->assertStringContainsString('data-hero-cta', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('"founder":{"@type":"Person","name":"عمر-بلا-صورة","jobTitle":"المدير التنفيذي"}', $html, 'organization semantics unchanged');
        $this->assertNoCustomerFacingEmptyState($html);
    }

    public function test_a_legal_page_shows_only_editor_wording_on_the_trust_center_surface(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);
        $legal = Page::factory()->create(['type' => PageType::Legal, 'slug' => 'privacy', 'title' => 'سياسة الخصوصية']);
        ContentBlock::factory()->for($legal)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص-السياسة-من-المحرر.</p>']]);
        SeoMetadata::factory()->for($legal)->create();
        $legal->update(['status' => PageStatus::Published]);
        $trust = Page::factory()->create(['type' => PageType::Trust, 'slug' => 'guarantee', 'title' => 'الضمان']);
        ContentBlock::factory()->for($trust)->create(['type' => 'rich_text', 'data' => ['content' => '<p>شروط-الضمان-من-المحرر.</p>']]);
        SeoMetadata::factory()->for($trust)->create();
        $trust->update(['status' => PageStatus::Published]);

        $legalHtml = $this->get('/privacy')->assertOk()->getContent();
        $trustHtml = $this->get('/guarantee')->assertOk()->getContent();

        $legalMain = mb_substr($legalHtml, mb_strpos($legalHtml, '<main'), mb_strpos($legalHtml, '</main>') - mb_strpos($legalHtml, '<main'));

        // The Trust Center redesign opens legal and trust pages on the shared
        // atmospheric hero and sets the policy prose on a white reading panel.
        // The enduring guarantees this test protects: the page shows the
        // editor's words, the reading surface is a plain readable prose panel,
        // the template invents no policy wording of its own, and there is one
        // H1. (The full design family is verified in TrustLegalPagesTest.)
        $this->assertStringContainsString('نص-السياسة-من-المحرر.', $legalHtml);
        $this->assertStringContainsString('prose prose-legal', $legalMain, 'legal prose sits on the readable white reading panel');
        $this->assertDoesNotMatchRegularExpression('/الاحتفاظ|الأساس القانوني|ملفات تعريف الارتباط|طرف ثالث|مسؤول حماية البيانات|نقل البيانات|نجمع بياناتك/u', $legalMain, 'the template invents no policy content');
        $this->assertSame(1, substr_count($legalHtml, '<h1'));

        $this->assertStringContainsString('surface-atmos', $trustHtml, 'a trust page opens on the shared Trust Center hero');
        $this->assertStringContainsString('شروط-الضمان-من-المحرر.', $trustHtml);
    }

    public function test_the_header_marks_exactly_one_contact_item_current_by_path_and_business_query(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);
        $contactUrl = preg_quote(route('public.contact'), '/');
        $businessUrl = preg_quote(route('public.contact', ['for' => 'business']), '/');

        $header = fn (string $html): string => mb_substr($html, mb_strpos($html, '<header'), mb_strpos($html, '</header>') - mb_strpos($html, '<header'));
        $contact = $header($this->get('/contact')->assertOk()->getContent());
        $business = $header($this->get('/contact?for=business')->assertOk()->getContent());
        $other = $header($this->get('/contact?for=anything-else')->assertOk()->getContent());

        $this->assertMatchesRegularExpression('/<a\s+href="'.$contactUrl.'"\s+aria-current="page"/u', $contact);
        $this->assertDoesNotMatchRegularExpression('/<a\s+href="'.$businessUrl.'"\s+aria-current="page"/u', $contact);
        $this->assertMatchesRegularExpression('/<a\s+href="'.$businessUrl.'"\s+aria-current="page"/u', $business);
        $this->assertDoesNotMatchRegularExpression('/<a\s+href="'.$contactUrl.'"\s+aria-current="page"/u', $business);
        $this->assertSame(1, substr_count($contact, 'aria-current="page"'));
        $this->assertSame(1, substr_count($business, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/<a\s+href="'.$contactUrl.'"\s+aria-current="page"/u', $other, 'any other value is the plain contact context');
    }

    public function test_the_header_never_links_to_the_quote_page_from_the_quote_page_and_keeps_its_row(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000', 'whatsapp_number' => '+966500000000']);
        $this->createCompliantServicePage(slug: 'hdr-svc');
        $header = fn (string $html): string => mb_substr($html, mb_strpos($html, '<header'), mb_strpos($html, '</header>') - mb_strpos($html, '<header'));

        foreach (['/', '/services', '/contact'] as $path) {
            $head = $header($this->get($path)->assertOk()->getContent());
            $this->assertStringContainsString('href="'.route('public.quote').'"', $head, $path.': the header keeps its one action');
            $this->assertStringContainsString('h-16 md:h-20', $head);
        }

        $quoteHead = $header($this->get('/quote')->assertOk()->getContent());
        $this->assertStringNotContainsString('href="'.route('public.quote').'"', $quoteHead, 'no self-link on /quote');
        $this->assertStringContainsString('h-16 md:h-20', $quoteHead, 'the row height is fixed, so nothing jumps');
        $this->assertMatchesRegularExpression('/<span class="invisible[^"]*" aria-hidden="true">/u', $quoteHead, 'an inert stand-in holds the slot');
    }

    public function test_the_contact_success_state_assumes_no_channel_and_promises_no_time(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);

        $this->post('/contact', ['name' => 'سلمى', 'phone' => '0500000000', 'message' => 'سؤال'])->assertRedirect('/contact');
        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('تم استلام رسالتك، وسنتواصل معك عبر بيانات التواصل التي أدخلتها.', $html);
        $this->assertStringNotContainsString('رقم الجوال الذي أدخلته', $html);
        $this->assertDoesNotMatchRegularExpression('/خلال \d|دقائق|ساعة واحدة|24 ساعة|فورًا|أسرع/u', $html);
    }

    public function test_the_quote_success_state_assumes_no_channel_and_promises_no_time(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);

        $this->post('/quote', ['name' => 'نورة', 'phone' => '0500000000'])->assertRedirect('/quote');
        $html = $this->get('/quote')->assertOk()->getContent();

        $this->assertStringContainsString('تم استلام طلب عرض السعر، وسنتواصل معك عبر بيانات التواصل التي أدخلتها لتأكيد التفاصيل.', $html);
        $this->assertStringNotContainsString('رقم الجوال الذي أدخلته', $html);
        $this->assertDoesNotMatchRegularExpression('/خلال \d|دقائق|ساعة واحدة|24 ساعة|فورًا|ضمان|أسرع/u', $html);
    }

    public function test_the_about_and_standalone_pages_trigger_no_lazy_loading_with_monograms(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'tagline' => 'وصف', 'founder_name' => 'مؤسس', 'story' => '<p>قصة.</p>']);
        TeamMember::factory()->count(2)->create(['photo_media_id' => null]);
        Page::factory()->create(['type' => PageType::About, 'slug' => 'about', 'title' => 'من نحن', 'status' => PageStatus::Published]);
        $legal = Page::factory()->create(['type' => PageType::Legal, 'slug' => 'terms', 'title' => 'الشروط']);
        ContentBlock::factory()->for($legal)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص.</p>']]);
        SeoMetadata::factory()->for($legal)->create();
        $legal->update(['status' => PageStatus::Published]);

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/about')->assertOk();
            $this->get('/terms')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }
}
