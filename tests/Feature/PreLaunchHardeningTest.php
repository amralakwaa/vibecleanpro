<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Pages\ManageBusinessProfile;
use App\Filament\Widgets\SystemReadinessWidget;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Seo\PublishingGate;
use App\Support\Analytics\AnalyticsSettings;
use App\Support\Readiness\LaunchReadiness;
use App\Support\Settings\IntegrationSettings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrustPagesDraftSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreLaunchHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function profile(array $attributes = []): BusinessProfile
    {
        // ManageBusinessProfile edits the row with id 1 (its getRecord()).
        $profile = (new BusinessProfile)->forceFill(['id' => 1, 'name' => 'Vibe Clean Pro', ...$attributes]);
        $profile->save();

        return $profile;
    }

    private function trustPage(string $slug, PageType $type, PageStatus $status = PageStatus::Draft, ?string $content = null): Page
    {
        $page = Page::factory()->create(['type' => $type, 'title' => $slug, 'slug' => $slug, 'status' => PageStatus::Draft]);

        if ($content !== null) {
            ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => $content]]);
        }

        if ($status !== PageStatus::Draft) {
            $page->update(['status' => $status]);
        }

        return $page->fresh();
    }

    private function setting(string $key, mixed $value, string $type = 'string'): void
    {
        SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
    }

    // ---- Phase 2: privacy & about drafts --------------------------------

    public function test_the_draft_seeder_writes_the_trust_pages_and_publishes_nothing(): void
    {
        $privacy = $this->trustPage('privacy', PageType::Legal);
        $about = $this->trustPage('about', PageType::About);

        $this->seed(TrustPagesDraftSeeder::class);

        foreach ([$privacy, $about] as $page) {
            $page->refresh();
            $this->assertSame(PageStatus::Draft, $page->status, 'the seeder publishes nothing - site:launch does');
        }

        // Both pages are now written in full - the privacy policy from what
        // the application actually collects, and About from the identity and
        // founder fields that CompanyProfileSeeder ships - so neither asks
        // the owner anything any more.
        foreach ([$privacy, $about] as $page) {
            $this->assertStringNotContainsString(PublishingGate::OWNER_INPUT_MARKER, json_encode($page->contentBlocks->pluck('data'), JSON_UNESCAPED_UNICODE));
        }
    }

    public function test_the_gate_refuses_a_page_that_still_carries_an_owner_marker(): void
    {
        $page = $this->trustPage('privacy', PageType::Legal, content: '<p>⚠ '.PublishingGate::OWNER_INPUT_MARKER.': مدة الاحتفاظ.</p>');

        $page->update(['status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Draft, $page->fresh()->status);
        $this->assertContains('owner_input', app(PublishingGate::class)->evaluate($page->fresh())->errors()->pluck('key')->all());
    }

    public function test_the_draft_seeder_never_touches_an_edited_page(): void
    {
        $privacy = $this->trustPage('privacy', PageType::Legal, content: '<p>نص كتبه المالك.</p>');

        $this->seed(TrustPagesDraftSeeder::class);

        $this->assertSame(1, $privacy->contentBlocks()->count());
        $this->assertSame('<p>نص كتبه المالك.</p>', $privacy->contentBlocks()->first()->data['content']);
    }

    public function test_forms_work_without_a_privacy_link_while_the_policy_is_a_draft(): void
    {
        $this->trustPage('privacy', PageType::Legal, content: '<p>مسودة</p>');

        foreach (['/quote', '/contact'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('سياسة الخصوصية</a>', false);
        }
    }

    public function test_forms_link_the_privacy_policy_once_it_is_published(): void
    {
        $this->trustPage('privacy', PageType::Legal, PageStatus::Published, '<p>سياسة معتمدة.</p>');

        foreach (['/quote', '/contact'] as $url) {
            $this->get($url)->assertOk()->assertSee('href="'.url('/privacy').'"', false);
        }
    }

    // ---- Phase 3: business profile fields -------------------------------

    public function test_the_commercial_registration_shows_only_when_entered_and_switched_on(): void
    {
        $profile = $this->profile(['commercial_registration_number' => '1010123456']);
        $this->get('/')->assertOk()->assertDontSee('1010123456');

        $profile->update(['display_commercial_registration' => true]);
        $this->get('/')->assertSee('السجل التجاري')->assertSee('1010123456');

        $profile->update(['commercial_registration_number' => null]);
        $this->get('/')->assertDontSee('السجل التجاري:');
    }

    public function test_no_article_claims_a_registration_number_for_the_company(): void
    {
        $content = require database_path('seeders/content/articles.php');

        foreach ($content['articles'] as $article) {
            $this->assertDoesNotMatchRegularExpression('/سجل\S*\s+(التجاري\s+)?(رقم\s+)?\d{6,}/u', $article['body'], $article['slug']);
        }
    }

    public function test_the_business_profile_screen_saves_the_new_fields(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $this->actingAs($user);
        $this->profile();

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm([
                'name' => 'Vibe Clean Pro',
                'service_area' => 'الرياض',
                'google_review_url' => 'https://g.page/r/example/review',
                'google_business_profile_url' => 'not-a-url',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_business_profile_url']);

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm(['name' => 'Vibe Clean Pro', 'service_area' => 'الرياض', 'google_review_url' => 'https://g.page/r/example/review', 'google_business_profile_url' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://g.page/r/example/review', BusinessProfile::query()->value('google_review_url'));
    }

    // ---- Phase 4: lead notification readiness ---------------------------

    public function test_lead_notifications_are_not_configured_without_an_inbox_or_a_real_mailer(): void
    {
        $this->profile();
        $this->assertSame('not_configured', app(LaunchReadiness::class)->leadNotifications()->state);

        // An inbox alone is not enough while no transport can deliver it.
        BusinessProfile::query()->update(['lead_notification_email' => 'team@example.test']);
        config(['mail.default' => 'log']);
        $this->assertSame('error', app(LaunchReadiness::class)->leadNotifications()->state);

        $settings = app(IntegrationSettings::class);
        $settings->set(IntegrationSettings::SMTP_ENABLED, true, 'bool');
        $settings->set(IntegrationSettings::SMTP_HOST, 'smtp.provider.test.invalid');
        $settings->set(IntegrationSettings::SMTP_FROM_ADDRESS, 'no-reply@vibecleanpro.com');
        $settings->putSecret(IntegrationSettings::SMTP_PASSWORD, 'secret');
        config(['mail.default' => 'smtp']);

        $this->assertSame('ready', app(LaunchReadiness::class)->leadNotifications()->state);
    }

    public function test_the_readiness_widget_warns_in_the_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $this->actingAs($user);
        $this->profile();

        Livewire::test(SystemReadinessWidget::class)
            ->assertOk()
            ->assertSee('إشعارات الطلبات')
            ->assertSee('غير مهيأة');
    }

    // ---- Phase 7: Search Console + GA4 ----------------------------------

    public function test_search_console_verification_prints_only_a_valid_token(): void
    {
        // Printing is switched on; the token itself decides the rest.
        $this->setting(IntegrationSettings::SEARCH_CONSOLE_ENABLED, '1', 'bool');
        $this->setting(AnalyticsSettings::SEARCH_CONSOLE_KEY, '<meta name="x">');
        $this->get('/')->assertDontSee('google-site-verification', false);

        $this->setting(AnalyticsSettings::SEARCH_CONSOLE_KEY, 'AbC123_def-456ghi');
        $this->get('/')->assertSee('<meta name="google-site-verification" content="AbC123_def-456ghi">', false);
    }

    public function test_ga4_stays_off_until_enabled_and_the_privacy_policy_is_published(): void
    {
        $analytics = app(AnalyticsSettings::class);
        $this->assertSame('not_configured', $analytics->ga4State());

        $this->setting(AnalyticsSettings::GA4_ID_KEY, 'G-ABC1234');
        $this->assertSame('configured_disabled', $analytics->ga4State());
        $this->get('/')->assertDontSee('googletagmanager.com/gtag', false);

        $this->setting(AnalyticsSettings::GA4_ENABLED_KEY, '1', 'bool');
        $this->assertSame('configured_disabled', $analytics->ga4State(), 'enabled but no published privacy policy');

        $this->trustPage('privacy', PageType::Legal, PageStatus::Published, '<p>سياسة معتمدة.</p>');
        $this->assertSame('active', $analytics->ga4State());
        $this->get('/')->assertSee('googletagmanager.com/gtag/js?id=G-ABC1234', false);
    }

    // ---- Phase 8: Google review CTA -------------------------------------

    public function test_the_review_cta_is_hidden_without_a_real_link(): void
    {
        $this->profile();

        $this->get('/contact')->assertOk()->assertDontSee('قيّم تجربتك معنا على Google');
    }

    public function test_the_review_cta_asks_for_an_honest_review_only(): void
    {
        $this->profile(['google_review_url' => 'https://g.page/r/example/review']);

        $html = $this->get('/contact')->assertOk()->assertSee('قيّم تجربتك معنا على Google')->assertSee('https://g.page/r/example/review', false)->getContent();

        foreach (['5 نجوم', 'خمس نجوم', 'خصم', 'هدية', 'مقابل تقييم', 'تقييم إيجابي'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html);
        }
    }
}
