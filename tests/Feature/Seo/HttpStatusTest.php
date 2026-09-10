<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Covers PublicPageController's status-code policy end to end through real
 * HTTP requests against the routes it is wired to.
 */
class HttpStatusTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_published_and_due_page_returns_200(): void
    {
        $this->createCompliantServicePage(slug: 'live-service');

        $this->get('/services/live-service')->assertOk();
    }

    public function test_a_draft_page_returns_404_not_a_soft_404_with_200(): void
    {
        $this->makeBarePage(['type' => PageType::Service, 'status' => PageStatus::Draft, 'slug' => 'draft-service']);

        $this->get('/services/draft-service')->assertNotFound();
    }

    public function test_a_review_page_returns_404(): void
    {
        $this->makeBarePage(['type' => PageType::Service, 'status' => PageStatus::Review, 'slug' => 'review-service']);

        $this->get('/services/review-service')->assertNotFound();
    }

    public function test_a_published_page_scheduled_in_the_future_returns_404(): void
    {
        // Built via the fully-compliant fixture so the Publishing Gate has
        // no reason to revert it to Draft - the 404 asserted below must
        // come from "scheduled in the future", not from a reverted status.
        $page = $this->createCompliantServicePage(slug: 'future-service');
        $page->published_at = now()->addWeek();
        $page->save();

        $this->assertSame(PageStatus::Published, $page->fresh()->status, 'Fixture must stay Published to actually exercise the future-scheduling branch.');
        $this->get('/services/future-service')->assertNotFound();
    }

    public function test_an_archived_page_returns_410_gone(): void
    {
        $this->makeBarePage(['type' => PageType::Service, 'status' => PageStatus::Archived, 'slug' => 'retired-service']);

        $this->get('/services/retired-service')->assertStatus(410);
    }

    public function test_an_unknown_slug_with_no_redirect_returns_404(): void
    {
        $this->get('/services/this-does-not-exist')->assertNotFound();
    }

    public function test_a_soft_deleted_page_returns_404_not_the_stale_content(): void
    {
        $page = $this->createCompliantServicePage(slug: 'about-to-be-deleted');
        $page->delete();

        $this->get('/services/about-to-be-deleted')->assertNotFound();
    }

    public function test_an_old_slug_with_an_active_redirect_issues_the_redirect_and_not_a_404(): void
    {
        Redirect::factory()->create(['from_path' => '/services/old-slug', 'to_path' => '/services/new-slug', 'type' => 301]);

        $this->get('/services/old-slug')
            ->assertStatus(301)
            ->assertRedirect('/services/new-slug');
    }

    public function test_a_deleted_page_with_a_manual_redirect_to_a_real_replacement_uses_that_redirects_status(): void
    {
        Redirect::factory()->create(['from_path' => '/services/discontinued', 'to_path' => '/services/replacement', 'type' => 302]);

        $this->get('/services/discontinued')->assertStatus(302);
    }

    public function test_a_miss_never_bounces_to_the_homepage(): void
    {
        // A silent redirect-to-homepage would show up here as a 200/302
        // instead of the 404 this asserts - the miss must stay a miss.
        $this->get('/services/totally-unknown-slug')->assertNotFound();
    }

    public function test_a_standalone_trust_or_legal_page_resolves_at_the_root_and_returns_200_when_published(): void
    {
        $page = $this->makeBarePage([
            'type' => PageType::Legal,
            'status' => PageStatus::Draft,
            'slug' => 'privacy-policy',
            'title' => 'سياسة الخصوصية',
        ]);
        ContentBlock::factory()->for($page)->create([
            'type' => 'rich_text',
            'data' => ['content' => 'نص سياسة الخصوصية الفعلي هنا.'],
        ]);
        $page->status = PageStatus::Published;
        $page->published_at = now();
        $page->save();

        $this->get('/privacy-policy')->assertOk();
    }

    public function test_the_admin_path_is_never_swallowed_by_the_standalone_catch_all_route(): void
    {
        // No admin user/session here on purpose: this only proves routing
        // reaches the Filament panel (redirect to its login), not the
        // standalone-page 404 controller.
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
