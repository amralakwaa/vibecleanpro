<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\ContentBlock;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A district page names the side of Riyadh it belongs to in its context
 * chip. Until now that name was always plain text, so the three published
 * side pages had exactly one inbound link each - from /areas - while forty
 * district pages named them and linked to none of them. The side pages read
 * as orphans to the SEO dashboard and were genuinely hard for a crawler to
 * reach by weight.
 *
 * Linking the name that is already on the page is the fix: no invented copy,
 * and the rule matches AreasIndexController exactly - a side is linked only
 * when a landing page has actually been published against that AreaGroup,
 * so a side never earns a URL just by existing.
 */
class AreaGroupHubLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_side_name_is_plain_text_until_its_page_is_published(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'شمال-الرياض-بلا-صفحة', 'slug' => 'north-unpublished']);
        $this->publishedArea('district-without-side', $group);

        $html = $this->get('/areas/district-without-side')->assertOk()->getContent();

        $this->assertStringContainsString('شمال-الرياض-بلا-صفحة', $html);
        $this->assertStringNotContainsString('href="/areas/north-unpublished"', $html);
    }

    public function test_a_draft_side_page_is_still_not_linked(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'شرق-الرياض-مسودة', 'slug' => 'east-draft']);
        $this->publishedArea('district-with-draft-side', $group);
        $this->sidePage($group, 'east-draft', PageStatus::Draft);

        $html = $this->get('/areas/district-with-draft-side')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="/areas/east-draft"', $html);
    }

    public function test_the_side_name_links_to_its_hub_once_that_page_is_published(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'وسط-الرياض-منشورة', 'slug' => 'central-published']);
        $this->publishedArea('district-with-side', $group);
        $this->sidePage($group, 'central-published', PageStatus::Published);

        $html = $this->get('/areas/district-with-side')->assertOk()->getContent();

        $this->assertStringContainsString('href="/areas/central-published"', $html);
        // The link has to carry the side's name as its anchor text; an
        // unlabelled or generic anchor would waste the only signal it sends.
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="\/areas\/central-published"[^>]*>\s*وسط-الرياض-منشورة\s*<\/a>/u',
            $html,
        );
    }

    public function test_a_district_with_no_side_at_all_renders_without_a_link(): void
    {
        $this->publishedArea('district-with-no-side');

        $this->get('/areas/district-with-no-side')
            ->assertOk()
            ->assertDontSee('href="/areas/"', escape: false);
    }

    private function publishedArea(string $slug, ?AreaGroup $group = null): Area
    {
        $area = Area::factory()->create(['name' => $slug, 'area_group_id' => $group?->id]);
        $page = Page::factory()->create(['type' => PageType::Area, 'slug' => $slug]);
        $area->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        return $area;
    }

    /**
     * pageable_* is not mass-assignable, so the association goes through the
     * relation. saveQuietly keeps PageObserver from reverting a Published
     * row that has no publishable body - the gate has its own tests.
     */
    private function sidePage(AreaGroup $group, string $slug, PageStatus $status): Page
    {
        $page = Page::factory()->create([
            'type' => PageType::Landing,
            'slug' => $slug,
            'published_at' => now()->subDay(),
        ]);
        $page->pageable()->associate($group);
        $page->status = $status;
        $page->saveQuietly();

        return $page;
    }
}
