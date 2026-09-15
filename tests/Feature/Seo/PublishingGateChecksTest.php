<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Seo\Enums\CheckSeverity;
use App\Seo\PublishingGate;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\PublishingGateResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Exercises PublishingGate::evaluate() check by check, in isolation from
 * the Observer/save lifecycle (see PublishingGateLifecycleTest for the
 * end-to-end save behavior). Each test starts from the fully-compliant
 * fixture and breaks exactly one rule, so a failure points at one check.
 */
class PublishingGateChecksTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private PublishingGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = app(PublishingGate::class);
    }

    private function severityOf(PublishingGateResult $result, string $key): ?CheckSeverity
    {
        return collect($result->checks)->firstWhere('key', $key)?->severity;
    }

    public function test_the_compliant_fixture_itself_has_zero_errors_and_zero_warnings(): void
    {
        $page = $this->createCompliantServicePage();

        $result = $this->gate->evaluate($page);

        $this->assertTrue($result->canPublish());
        $this->assertCount(0, $result->warnings(), 'Warnings present: '.$result->warnings()->pluck('key')->implode(', '));
    }

    public function test_a_blank_title_is_an_error(): void
    {
        $page = $this->makeBarePage(['title' => '']);

        $result = $this->gate->evaluate($page);

        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'title'));
        $this->assertFalse($result->canPublish());
    }

    public function test_an_invalid_slug_is_an_error(): void
    {
        $page = $this->makeBarePage(['slug' => 'Invalid Slug!']);

        $result = $this->gate->evaluate($page);

        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'slug'));
    }

    public function test_a_valid_lowercase_hyphenated_slug_passes(): void
    {
        $page = $this->makeBarePage(['slug' => 'valid-slug-123']);

        $result = $this->gate->evaluate($page);

        $this->assertSame(CheckSeverity::Pass, $this->severityOf($result, 'slug'));
    }

    public function test_an_entity_backed_page_type_with_no_linked_entity_is_an_error(): void
    {
        $page = $this->makeBarePage(['type' => PageType::Service]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'pageable'));
    }

    public function test_a_standalone_page_type_has_no_pageable_check_at_all(): void
    {
        $page = $this->makeBarePage(['type' => PageType::Landing]);

        $result = $this->gate->evaluate($page);

        $this->assertNull($this->severityOf($result, 'pageable'));
    }

    public function test_an_invalid_custom_canonical_is_an_error(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->forceFill(['canonical_url' => 'javascript:alert(1)'])->save();

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'canonical'));
        $this->assertFalse($result->canPublish());
    }

    public function test_manual_noindex_is_a_warning_that_still_allows_publishing(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['robots_index' => false]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'robots'));
        $this->assertTrue($result->canPublish(), 'An editorial noindex choice must never block publishing.');
    }

    public function test_a_conflicting_active_redirect_at_the_pages_own_path_is_an_error(): void
    {
        $page = $this->createCompliantServicePage(slug: 'conflicted-path');
        $ownPath = app(UrlResolver::class)->pathForPage($page);
        Redirect::factory()->create(['from_path' => $ownPath, 'to_path' => '/services/elsewhere', 'is_active' => true]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'redirect_conflict'));
        $this->assertFalse($result->canPublish());
    }

    public function test_an_inactive_redirect_at_the_pages_own_path_is_not_a_conflict(): void
    {
        $page = $this->createCompliantServicePage(slug: 'not-conflicted-path');
        $ownPath = app(UrlResolver::class)->pathForPage($page);
        Redirect::factory()->create(['from_path' => $ownPath, 'to_path' => '/services/elsewhere', 'is_active' => false]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Pass, $this->severityOf($result, 'redirect_conflict'));
    }

    public function test_empty_content_blocks_is_an_error(): void
    {
        $page = $this->createCompliantServicePage();
        $page->contentBlocks()->delete();

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'content_empty'));
        $this->assertFalse($result->canPublish());
    }

    public function test_an_about_page_counts_the_company_identity_as_content(): void
    {
        $page = Page::factory()->create(['type' => PageType::About, 'slug' => 'about', 'title' => 'من نحن']);
        SeoMetadata::factory()->for($page)->create();

        // No blocks and no identity data: still an error.
        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));
        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'content_empty'));

        // Identity entered on the business profile: the page has content.
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'identity_statement' => 'شركة سعودية محلية تخدم الرياض.']);
        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));
        $this->assertSame(CheckSeverity::Pass, $this->severityOf($result, 'content_empty'));
        $this->assertTrue($result->canPublish());

        // The exemption is About-only: a Trust page with the same profile still needs blocks.
        $trust = Page::factory()->create(['type' => PageType::Trust, 'slug' => 'trust-empty', 'title' => 'ثقة']);
        $this->assertSame(CheckSeverity::Error, $this->severityOf($this->gate->evaluate($trust->fresh(['contentBlocks', 'seoMetadata', 'pageable'])), 'content_empty'));
    }

    public function test_a_second_about_page_is_blocked_while_another_is_published(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'tagline' => 'وصف']);
        $live = Page::factory()->create(['type' => PageType::About, 'slug' => 'about', 'title' => 'من نحن', 'status' => PageStatus::Published]);
        $second = Page::factory()->create(['type' => PageType::About, 'slug' => 'about-2', 'title' => 'ثانية']);

        $result = $this->gate->evaluate($second->fresh(['contentBlocks', 'seoMetadata', 'pageable']));
        $this->assertSame(CheckSeverity::Error, $this->severityOf($result, 'about_singleton'));
        $this->assertFalse($result->canPublish());

        // The live page itself is not in conflict with itself, and a
        // Trust page is never subject to the rule.
        $this->assertSame(CheckSeverity::Pass, $this->severityOf($this->gate->evaluate($live->fresh(['contentBlocks', 'seoMetadata', 'pageable'])), 'about_singleton'));
        $trust = Page::factory()->create(['type' => PageType::Trust, 'slug' => 'trust-x', 'title' => 'ثقة']);
        $this->assertNull($this->severityOf($this->gate->evaluate($trust->fresh(['contentBlocks', 'seoMetadata', 'pageable'])), 'about_singleton'));

        $live->update(['status' => PageStatus::Draft]);
        $this->assertSame(CheckSeverity::Pass, $this->severityOf($this->gate->evaluate($second->fresh(['contentBlocks', 'seoMetadata', 'pageable'])), 'about_singleton'));
    }

    public function test_missing_seo_title_is_a_warning_not_an_error(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['meta_title' => null]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'seo_title'));
        $this->assertTrue($result->canPublish());
    }

    public function test_missing_meta_description_is_a_warning_not_an_error(): void
    {
        $page = $this->createCompliantServicePage();
        $page->seoMetadata->update(['meta_description' => null]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'meta_description'));
        $this->assertTrue($result->canPublish());
    }

    public function test_a_service_with_no_featured_image_gets_a_warning(): void
    {
        $page = $this->createCompliantServicePage();
        $page->pageable->update(['featured_media_id' => null]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'featured_image'));
        $this->assertTrue($result->canPublish());
    }

    public function test_a_featured_image_with_blank_alt_text_gets_a_warning(): void
    {
        $page = $this->createCompliantServicePage();
        $media = Media::factory()->create(['alt_text' => null]);
        $page->pageable->update(['featured_media_id' => $media->id]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'image_alt'));
    }

    public function test_a_project_type_page_is_never_checked_for_a_single_images_alt_text_since_it_uses_a_gallery(): void
    {
        $page = $this->makeBarePage(['type' => PageType::Project]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertNull($this->severityOf($result, 'image_alt'));
    }

    public function test_zero_inbound_internal_links_is_a_warning_not_an_error(): void
    {
        $media = Media::factory()->create();
        $service = Service::factory()->create(['featured_media_id' => $media->id]);
        // No areas/projects attached and no explicit InternalLink row: zero
        // inbound signal from either source.
        $page = Page::factory()->create([
            'type' => PageType::Service,
            'slug' => 'friendless-service',
            'title' => 'خدمة بلا روابط واردة',
        ]);
        $service->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'محتوى فعلي هنا.']]);
        ContentBlock::factory()->for($page)->create(['type' => 'cta', 'data' => ['label' => 'اطلب الآن']]);
        SeoMetadata::factory()->for($page)->create();

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'internal_links'));
        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'related_content'));
        $this->assertTrue($result->canPublish(), 'A lack of inbound links is an editorial warning, never a publish-blocking error.');
    }

    public function test_a_commercial_page_type_without_a_cta_block_gets_a_warning(): void
    {
        $page = $this->createCompliantServicePage();
        $page->contentBlocks()->where('type', 'cta')->delete();

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'cta'));
        $this->assertTrue($result->canPublish());
    }

    public function test_a_non_commercial_page_type_is_never_checked_for_a_cta(): void
    {
        $page = $this->makeBarePage(['type' => PageType::Landing]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertNull($this->severityOf($result, 'cta'));
    }

    public function test_an_article_without_an_author_gets_a_warning(): void
    {
        $article = Article::factory()->create(['author_id' => null]);
        $page = Page::factory()->create(['type' => PageType::Article, 'slug' => 'authorless-article']);
        $article->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'مقال حقيقي هنا.']]);

        $result = $this->gate->evaluate($page->fresh(['contentBlocks', 'seoMetadata', 'pageable']));

        $this->assertSame(CheckSeverity::Warning, $this->severityOf($result, 'article_author'));
        $this->assertTrue($result->canPublish());
    }
}
