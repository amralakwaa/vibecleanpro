<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Page;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * The About page must say nothing about the company that an editor did
 * not enter. These tests pin that: every identity/founder/team fact is
 * read from the business profile or team_members, changes when that data
 * changes, disappears when it is hidden - and a bare profile renders a
 * page with no invented claims at all.
 */
class AboutPageTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_published_about_page_renders_the_managed_identity_founder_and_team(): void
    {
        $this->profile([
            'tagline' => 'وصف-مختصر-من-الإدارة',
            'identity_statement' => 'بيان-الهوية-من-الإدارة',
            'story' => '<p>قصة-التأسيس-من-الإدارة</p>',
            'mission' => 'رسالة-من-الإدارة',
            'values' => [['title' => 'مبدأ-من-الإدارة', 'description' => 'شرح-المبدأ']],
            'founder_name' => 'اسم-المؤسس-من-الإدارة',
            'founder_title' => 'صفة-المؤسس',
            'founder_bio' => 'نبذة-المؤسس',
        ]);
        TeamMember::factory()->create(['name' => 'عضو-ظاهر', 'role_title' => 'دور-العضو']);
        $this->aboutPage();

        $html = $this->get('/about')->assertOk()->getContent();

        foreach (['وصف-مختصر-من-الإدارة', 'بيان-الهوية-من-الإدارة', 'قصة-التأسيس-من-الإدارة', 'رسالة-من-الإدارة', 'مبدأ-من-الإدارة', 'شرح-المبدأ', 'اسم-المؤسس-من-الإدارة', 'صفة-المؤسس', 'نبذة-المؤسس', 'عضو-ظاهر', 'دور-العضو'] as $fact) {
            $this->assertStringContainsString($fact, $html);
        }
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_changing_the_founder_name_in_the_profile_changes_the_public_page(): void
    {
        $profile = $this->profile(['founder_name' => 'الاسم-الأول']);
        $this->aboutPage();

        $this->assertStringContainsString('الاسم-الأول', $this->get('/about')->getContent());

        $profile->update(['founder_name' => 'الاسم-بعد-التعديل']);

        $html = $this->get('/about')->getContent();
        // The visible heading itself carries the edited name - not just
        // the structured data - so a template with a typed-in name fails.
        $this->assertMatchesRegularExpression('/<h3[^>]*>\s*الاسم-بعد-التعديل\s*<\/h3>/u', $html);
        $this->assertStringNotContainsString('الاسم-الأول', $html);
    }

    public function test_hiding_the_founder_removes_the_whole_section_without_deleting_the_data(): void
    {
        $profile = $this->profile(['founder_name' => 'مؤسس-مخفي-لاحقًا', 'show_founder' => true]);
        $this->aboutPage();

        $this->assertStringContainsString('مؤسس-مخفي-لاحقًا', $this->get('/about')->getContent());

        $profile->update(['show_founder' => false]);

        $html = $this->get('/about')->getContent();
        $this->assertStringNotContainsString('مؤسس-مخفي-لاحقًا', $html);
        $this->assertDoesNotMatchRegularExpression('/<h2[^>]*>المؤسس<\/h2>/u', $html);
        $this->assertSame('مؤسس-مخفي-لاحقًا', $profile->fresh()->founder_name);

        // A visible toggle with no name is equally nothing to show.
        $profile->update(['show_founder' => true, 'founder_name' => null]);
        $this->assertDoesNotMatchRegularExpression('/<h2[^>]*>المؤسس<\/h2>/u', $this->get('/about')->getContent());
    }

    public function test_only_active_team_members_appear_in_sort_order_and_the_section_can_be_switched_off(): void
    {
        $profile = $this->profile();
        $this->aboutPage();
        // Created out of display order, and the earliest-created is the
        // one meant to appear last - so only sort_order can order them.
        TeamMember::factory()->create(['name' => 'عضو-الثالث', 'sort_order' => 30]);
        TeamMember::factory()->create(['name' => 'عضو-الأول', 'sort_order' => 10]);
        TeamMember::factory()->create(['name' => 'عضو-الثاني', 'sort_order' => 20]);
        TeamMember::factory()->inactive()->create(['name' => 'عضو-غير-نشط', 'sort_order' => 0]);

        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringContainsString('عضو-الأول', $html);
        $this->assertStringNotContainsString('عضو-غير-نشط', $html);
        $this->assertLessThan(mb_strpos($html, 'عضو-الثاني'), mb_strpos($html, 'عضو-الأول'));
        $this->assertLessThan(mb_strpos($html, 'عضو-الثالث'), mb_strpos($html, 'عضو-الثاني'));
        $this->assertMatchesRegularExpression('/<h2[^>]*>الفريق<\/h2>/u', $html);

        $profile->update(['show_team' => false]);
        $off = $this->get('/about')->getContent();
        $this->assertStringNotContainsString('عضو-الأول', $off);
        $this->assertDoesNotMatchRegularExpression('/<h2[^>]*>الفريق<\/h2>/u', $off);
        $this->assertSame(4, TeamMember::query()->count());
    }

    public function test_a_bare_profile_renders_no_section_no_placeholder_and_no_invented_claim(): void
    {
        $this->profile();
        $this->aboutPage();

        $html = $this->get('/about')->assertOk()->getContent();

        foreach (['قصتنا', 'ما نسعى إليه', 'مبادئ العمل', 'المؤسس', 'الفريق</h2>'] as $label) {
            $this->assertStringNotContainsString($label, $html);
        }
        // Business facts never come from the template: no nationality,
        // no founder, no experience or customer numbers unless entered.
        $this->assertDoesNotMatchRegularExpression('/سعودي|الأكوع|خبرة|الأفضل|آلاف|منذ سنوات|عملاء راضون|\d+ عميل|لا توجد|لم تتم/u', $html);

        // Even with every section switched on, the only founder name on
        // the page is the one the editor typed.
        BusinessProfile::query()->update(['founder_name' => 'اسم-اختباري', 'founder_title' => 'صفة', 'story' => '<p>قصة.</p>']);
        TeamMember::factory()->create(['name' => 'عضو-اختباري']);
        $full = $this->get('/about')->getContent();
        $this->assertStringContainsString('اسم-اختباري', $full);
        $this->assertDoesNotMatchRegularExpression('/الأكوع|سعودي/u', $full);
    }

    public function test_coverage_and_evidence_counts_come_from_published_records_only(): void
    {
        $this->profile(['city' => 'مدينة-من-الإدارة']);
        $this->aboutPage();

        // Two published areas + one draft: below the threshold, no claim.
        $this->createCompliantAreaPage(slug: 'area-a');
        $this->createCompliantAreaPage(slug: 'area-b');
        $this->createCompliantAreaPage(slug: 'area-draft', status: PageStatus::Draft);

        $html = $this->get('/about')->getContent();
        $this->assertStringContainsString('مدينة-من-الإدارة', $html);
        $this->assertStringNotContainsString('مناطق التغطية</dt>', $html);

        $this->createCompliantAreaPage(slug: 'area-c');
        $html = $this->get('/about')->getContent();
        $this->assertStringContainsString('مناطق التغطية</dt>', $html);
        $this->assertStringContainsString('3 أحياء', $html);
        $this->assertStringContainsString('href="'.route('public.areas.index').'"', $html);
    }

    public function test_the_about_page_follows_publication_and_joins_the_navigation_only_when_published(): void
    {
        $this->profile();
        $page = $this->aboutPage(status: PageStatus::Draft);

        $this->get('/about')->assertNotFound();
        $this->assertStringNotContainsString('href="'.url('/about').'"', $this->get('/services')->getContent());

        $page->update(['status' => PageStatus::Published]);

        $this->get('/about')->assertOk();
        $this->assertStringContainsString('href="'.url('/about').'"', $this->get('/services')->getContent());
    }

    public function test_editor_blocks_and_faqs_render_below_the_identity_sections(): void
    {
        $this->profile(['founder_name' => 'مؤسس-قبل-الكتل']);
        $page = $this->aboutPage();
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'position' => 1, 'data' => ['content' => '<p>كتلة-إضافية-من-المحرر</p>']]);
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'position' => 2, 'data' => ['heading' => 'أسئلة-عن-الشركة']]);
        Faq::factory()->for($page)->create(['question' => 'سؤال-عن-الشركة؟', 'answer' => 'إجابة.']);

        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringContainsString('كتلة-إضافية-من-المحرر', $html);
        $this->assertSame(1, substr_count($html, 'سؤال-عن-الشركة؟'));
        $this->assertLessThan(mb_strpos($html, 'كتلة-إضافية-من-المحرر'), mb_strpos($html, 'مؤسس-قبل-الكتل'));
    }

    public function test_the_founder_is_in_the_sitewide_structured_data_only_while_visible(): void
    {
        $profile = $this->profile(['founder_name' => 'مؤسس-في-السكيما', 'founder_title' => 'صفة-في-السكيما']);
        $this->aboutPage();

        $html = $this->get('/about')->getContent();
        $this->assertStringContainsString('"founder":{"@type":"Person","name":"مؤسس-في-السكيما","jobTitle":"صفة-في-السكيما"}', $html);

        $profile->update(['show_founder' => false]);
        $this->assertStringNotContainsString('"founder"', $this->get('/about')->getContent());
    }

    public function test_only_one_about_page_can_be_published_at_a_time(): void
    {
        $this->profile();
        $first = $this->aboutPage();
        $this->assertSame(PageStatus::Published, $first->fresh()->status);

        // Every save path runs the Publishing Gate through PageObserver, so
        // the second page is reverted to Draft even when created directly.
        $second = Page::factory()->create(['type' => PageType::About, 'slug' => 'about-2', 'title' => 'هوية-ثانية', 'status' => PageStatus::Published]);

        $this->assertSame(PageStatus::Draft, $second->fresh()->status);
        $this->assertSame(1, Page::query()->where('type', PageType::About)->published()->count());
        $this->get('/about-2')->assertNotFound();
        $this->get('/about')->assertOk();

        // Unpublish the first, and the second can take over - one at a time.
        $first->update(['status' => PageStatus::Draft]);
        $second->refresh()->update(['status' => PageStatus::Published]);
        $this->assertSame(PageStatus::Published, $second->fresh()->status);
        $this->get('/about-2')->assertOk();
        $this->assertStringContainsString('href="'.url('/about-2').'"', $this->get('/services')->getContent());
        $this->assertStringNotContainsString('href="'.url('/about').'"', $this->get('/services')->getContent());
    }

    public function test_the_page_triggers_no_lazy_loading(): void
    {
        $photo = Media::factory()->create();
        $this->profile(['founder_name' => 'مؤسس', 'founder_photo_media_id' => $photo->id, 'values' => [['title' => 'مبدأ']]]);
        TeamMember::factory()->count(2)->create(['photo_media_id' => Media::factory()->create()->id]);
        TeamMember::factory()->create();
        $page = $this->aboutPage();
        ContentBlock::factory()->for($page)->create(['type' => 'faq', 'data' => ['heading' => 'أسئلة']]);
        Faq::factory()->for($page)->create();

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/about')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    /**
     * A tagline is the minimum identity content the Publishing Gate
     * accepts for an About page (see PublishingGate::checkContentNotEmpty).
     */
    private function profile(array $attributes = []): BusinessProfile
    {
        return BusinessProfile::query()->create(['tagline' => 'وصف-مختصر', ...$attributes, 'name' => 'Vibe Clean Pro']);
    }

    private function aboutPage(PageStatus $status = PageStatus::Published): Page
    {
        return Page::factory()->create(['type' => PageType::About, 'slug' => 'about', 'title' => 'من نحن', 'status' => $status]);
    }
}
