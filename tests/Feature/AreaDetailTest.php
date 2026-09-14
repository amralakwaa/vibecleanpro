<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\Article;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class AreaDetailTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_attaching_a_service_to_an_area_never_auto_creates_a_servicexarea_page(): void
    {
        $countBefore = Page::query()->count();

        $area = Area::factory()->create();
        $service = Service::factory()->create();
        $area->services()->attach($service);

        $this->assertSame($countBefore, Page::query()->count(), 'Linking a Service to an Area must never silently create a Service x Area page.');
    }

    public function test_the_area_hero_carries_a_request_quote_cta_prefilled_with_this_area(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-quote-cta');

        $response = $this->get('/areas/area-quote-cta');

        $response->assertSee(route('public.quote').'?area='.$page->pageable->id, false);
    }

    public function test_the_h1_is_the_real_cms_title_never_a_generated_pattern(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-real-title', title: 'تنظيف منازل في حي الياسمين بخبرة محلية');

        $response = $this->get('/areas/area-real-title');

        $response->assertSee('تنظيف منازل في حي الياسمين بخبرة محلية');
    }

    public function test_services_section_is_hidden_and_no_empty_state_shown_when_the_area_has_no_services(): void
    {
        $area = Area::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Area, 'slug' => 'area-no-services', 'status' => PageStatus::Draft]);
        $area->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى محلي كافٍ لهذه المنطقة.']]);
        $page->update(['status' => PageStatus::Published]);

        $response = $this->get('/areas/area-no-services');

        $response->assertOk();
        $response->assertDontSee('الخدمات المتاحة');
        $response->assertDontSee('لا توجد خدمات مرتبطة بهذه المنطقة بعد');
    }

    public function test_only_actively_linked_services_with_a_published_page_are_shown(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-with-services');
        $area = $page->pageable;

        $activeService = $this->createPublishedService('تنظيف السجاد المحلي', slug: 'active-local-service');
        $area->services()->attach($activeService, ['is_active' => true]);

        $inactivePivotService = $this->createPublishedService('خدمة موقوفة محليًا', slug: 'inactive-pivot-service');
        $area->services()->attach($inactivePivotService, ['is_active' => false]);

        $unpublishedService = Service::factory()->create(['name' => 'خدمة بلا صفحة منشورة']);
        $area->services()->attach($unpublishedService, ['is_active' => true]);

        $response = $this->get('/areas/area-with-services');

        $response->assertSee('تنظيف السجاد المحلي');
        $response->assertDontSee('خدمة موقوفة محليًا');
        $response->assertDontSee('خدمة بلا صفحة منشورة');
    }

    public function test_projects_section_is_hidden_when_the_area_has_no_projects(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-no-projects');

        $response = $this->get('/areas/area-no-projects');

        $response->assertDontSee('نتائج حقيقية في هذه المنطقة');
    }

    public function test_a_project_with_real_before_and_after_photos_renders_the_before_after_showcase(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-before-after');
        $area = $page->pageable;

        $project = $this->createPublishedProjectForArea('تنظيف فيلا كاملة', slug: 'area-project-before-after', area: $area);
        $before = Media::factory()->create();
        $after = Media::factory()->create();
        $project->media()->attach($before->id, ['stage' => MediaStage::Before->value, 'sort_order' => 0]);
        $project->media()->attach($after->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);

        $response = $this->get('/areas/area-before-after');

        $response->assertSee('تنظيف فيلا كاملة');
        $response->assertSee('قبل');
        $response->assertSee('بعد');
    }

    public function test_a_project_with_only_an_after_photo_renders_as_a_plain_project_card(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-after-only');
        $area = $page->pageable;

        $project = $this->createPublishedProjectForArea('تنظيف شقة مفروشة', slug: 'area-project-after-only', area: $area);
        $after = Media::factory()->create();
        $project->media()->attach($after->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);

        $response = $this->get('/areas/area-after-only');

        $response->assertSee('تنظيف شقة مفروشة');
        $response->assertDontSee('قبل');
    }

    public function test_offers_section_is_hidden_when_the_area_has_no_offers(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-no-offers');

        $response = $this->get('/areas/area-no-offers');

        $response->assertDontSee('عروض حالية في هذه المنطقة');
    }

    public function test_only_the_active_offer_shows_when_scheduled_and_expired_offers_also_exist(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-offer-availability');
        $area = $page->pageable;

        $active = $this->createPublishedOffer('عرض نشط بالمنطقة', slug: 'area-active-offer', isActive: true, startsAt: now()->subDay(), endsAt: now()->addWeek());
        $active->areas()->attach($area);

        $scheduled = $this->createPublishedOffer('عرض قادم بالمنطقة', slug: 'area-scheduled-offer', isActive: true, startsAt: now()->addWeek(), endsAt: now()->addMonth());
        $scheduled->areas()->attach($area);

        $expired = $this->createPublishedOffer('عرض منتهٍ بالمنطقة', slug: 'area-expired-offer', isActive: true, startsAt: now()->subMonth(), endsAt: now()->subWeek());
        $expired->areas()->attach($area);

        $response = $this->get('/areas/area-offer-availability');

        $response->assertSee('عرض نشط بالمنطقة');
        $response->assertDontSee('عرض قادم بالمنطقة');
        $response->assertDontSee('عرض منتهٍ بالمنطقة');
    }

    public function test_testimonials_section_is_hidden_when_the_area_has_none(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-no-testimonials');

        $response = $this->get('/areas/area-no-testimonials');

        $response->assertDontSee('آراء العملاء في هذه المنطقة', false);
    }

    public function test_a_testimonial_linked_to_the_area_is_shown(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-with-testimonial');
        $area = $page->pageable;

        Testimonial::factory()->create(['area_id' => $area->id, 'author_name' => 'فهد المطيري', 'content' => 'التزام رائع بالمواعيد في منطقتنا.']);

        $response = $this->get('/areas/area-with-testimonial');

        $response->assertSee('فهد المطيري');
        $response->assertSee('التزام رائع بالمواعيد في منطقتنا.');
    }

    public function test_a_testimonial_linked_to_a_different_area_is_not_shown(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-without-this-testimonial');
        $otherArea = Area::factory()->create();

        Testimonial::factory()->create(['area_id' => $otherArea->id, 'author_name' => 'عميل منطقة أخرى']);

        $response = $this->get('/areas/area-without-this-testimonial');

        $response->assertDontSee('عميل منطقة أخرى');
    }

    public function test_articles_section_is_hidden_when_the_area_has_none(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-no-articles');

        $response = $this->get('/areas/area-no-articles');

        $response->assertDontSee('مقالات مرتبطة بهذه المنطقة');
    }

    public function test_a_published_article_linked_to_the_area_is_shown(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-with-article');
        $area = $page->pageable;

        $article = $this->createPublishedArticle('دليل تنظيف المنازل في هذا الحي', slug: 'area-article-guide');
        $article->areas()->attach($area);

        $response = $this->get('/areas/area-with-article');

        $response->assertSee('دليل تنظيف المنازل في هذا الحي');
    }

    public function test_an_unpublished_articles_page_is_never_shown_even_if_linked(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-with-draft-article');
        $area = $page->pageable;

        $article = Article::factory()->create(['title' => 'مقال لم يُنشر بعد']);
        $draftPage = Page::factory()->create(['type' => PageType::Article, 'title' => 'مقال لم يُنشر بعد', 'slug' => 'draft-article-area', 'status' => PageStatus::Draft]);
        $article->page()->save($draftPage);
        $article->areas()->attach($area);

        $response = $this->get('/areas/area-with-draft-article');

        $response->assertDontSee('مقال لم يُنشر بعد');
    }

    public function test_nearby_areas_are_limited_to_the_same_group_published_and_exclude_the_current_area(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-with-nearby');
        $area = $page->pageable;

        // Give the main area a real group so "same group" is meaningful.
        $area->update(['area_group_id' => AreaGroup::factory()->create()->id]);

        $sameGroupPublished = Area::factory()->create(['area_group_id' => $area->area_group_id, 'name' => 'منطقة قريبة منشورة']);
        $sameGroupPage = Page::factory()->create(['type' => PageType::Area, 'slug' => 'nearby-published', 'status' => PageStatus::Draft]);
        $sameGroupPublished->page()->save($sameGroupPage);
        ContentBlock::factory()->for($sameGroupPage)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى محلي كافٍ.']]);
        $sameGroupPage->update(['status' => PageStatus::Published]);

        $otherGroupPublished = Area::factory()->create(['name' => 'منطقة بمجموعة مختلفة']);
        $otherGroupPage = Page::factory()->create(['type' => PageType::Area, 'slug' => 'other-group-published', 'status' => PageStatus::Draft]);
        $otherGroupPublished->page()->save($otherGroupPage);
        ContentBlock::factory()->for($otherGroupPage)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى محلي كافٍ.']]);
        $otherGroupPage->update(['status' => PageStatus::Published]);

        $response = $this->get('/areas/area-with-nearby');

        $response->assertSee('منطقة قريبة منشورة');
        $response->assertDontSee('منطقة بمجموعة مختلفة');
    }

    public function test_final_cta_never_renders_a_broken_whatsapp_or_phone_link_when_unavailable(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-no-contact-channels');

        $response = $this->get('/areas/area-no-contact-channels');

        $response->assertOk();
        $response->assertDontSee('wa.me');
        $response->assertDontSee('tel:');
    }

    private function createPublishedService(string $name, string $slug): Service
    {
        $service = Service::factory()->create(['name' => $name]);

        $servicePage = Page::factory()->create([
            'type' => PageType::Service,
            'title' => $name,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $service->page()->save($servicePage);

        ContentBlock::factory()->for($servicePage)->create(['type' => 'rich_text', 'data' => ['content' => 'تفاصيل الخدمة.']]);

        $servicePage->update(['status' => PageStatus::Published]);

        return $service;
    }

    private function createPublishedProjectForArea(string $title, string $slug, Area $area): Project
    {
        $project = Project::factory()->create(['title' => $title, 'area_id' => $area->id]);

        $projectPage = Page::factory()->create([
            'type' => PageType::Project,
            'title' => $title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $project->page()->save($projectPage);

        ContentBlock::factory()->for($projectPage)->create(['type' => 'rich_text', 'data' => ['content' => 'تفاصيل المشروع.']]);

        $projectPage->update(['status' => PageStatus::Published]);

        return $project;
    }

    private function createPublishedOffer(string $title, string $slug, bool $isActive, $startsAt, $endsAt): Offer
    {
        $offer = Offer::factory()->create([
            'title' => $title,
            'is_active' => $isActive,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $offerPage = Page::factory()->create([
            'type' => PageType::Offer,
            'title' => $title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $offer->page()->save($offerPage);

        ContentBlock::factory()->for($offerPage)->create(['type' => 'rich_text', 'data' => ['content' => 'تفاصيل العرض.']]);

        $offerPage->update(['status' => PageStatus::Published]);

        return $offer;
    }

    private function createPublishedArticle(string $title, string $slug): Article
    {
        $article = Article::factory()->create(['title' => $title]);

        $articlePage = Page::factory()->create([
            'type' => PageType::Article,
            'title' => $title,
            'slug' => $slug,
            'status' => PageStatus::Draft,
        ]);
        $article->page()->save($articlePage);

        ContentBlock::factory()->for($articlePage)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى المقال.']]);

        $articlePage->update(['status' => PageStatus::Published]);

        return $article;
    }
}
