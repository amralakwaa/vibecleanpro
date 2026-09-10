<?php

namespace Tests\Feature\Seo;

use App\Enums\PageType;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SlugHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers RecordSlugChange (app/Seo/Actions/RecordSlugChange.php), wired via
 * PageObserver::updating(). Every scenario renames an already-persisted
 * Page's slug directly through Eloquent - no admin UI involved - to isolate
 * the slug-lifecycle behavior itself from any Filament form concern.
 */
class SlugChangeAndRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_a_slug_records_the_old_slug_in_history(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'old-name']);

        $page->update(['slug' => 'new-name']);

        $this->assertDatabaseHas('slug_history', ['page_id' => $page->id, 'slug' => 'old-name']);
    }

    public function test_changing_a_slug_creates_a_301_redirect_from_the_old_path_to_the_new_one(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'old-name']);

        $page->update(['slug' => 'new-name']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/services/old-name',
            'to_path' => '/services/new-name',
            'type' => 301,
            'source' => 'slug_change',
            'is_active' => 1,
        ]);
    }

    public function test_renaming_back_and_forth_does_not_duplicate_slug_history_rows(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'name-a']);

        $page->update(['slug' => 'name-b']);
        $page->update(['slug' => 'name-a']);
        $page->update(['slug' => 'name-b']);

        $this->assertSame(2, SlugHistory::query()->where('page_id', $page->id)->count());
    }

    public function test_renaming_a_page_back_to_a_previous_slug_never_creates_a_self_redirect(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'a']);

        $page->update(['slug' => 'b']); // creates /services/a -> /services/b
        $page->update(['slug' => 'a']); // back to the original slug

        $this->assertDatabaseMissing('redirects', ['from_path' => '/services/a', 'to_path' => '/services/a']);
        $this->assertSame(0, Redirect::query()->where('from_path', '/services/a')->count());
        $this->assertDatabaseHas('redirects', ['from_path' => '/services/b', 'to_path' => '/services/a']);
    }

    public function test_a_chain_of_two_renames_is_flattened_so_both_old_paths_point_straight_at_the_final_one(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'a']);

        $page->update(['slug' => 'b']); // /services/a -> /services/b
        $page->update(['slug' => 'c']); // flattens: /services/a -> /services/c, creates /services/b -> /services/c

        $this->assertDatabaseHas('redirects', ['from_path' => '/services/a', 'to_path' => '/services/c']);
        $this->assertDatabaseHas('redirects', ['from_path' => '/services/b', 'to_path' => '/services/c']);
        $this->assertDatabaseMissing('redirects', ['from_path' => '/services/a', 'to_path' => '/services/b']);
        $this->assertSame(2, Redirect::query()->count(), 'Flattening must not leave a stray intermediate redirect behind.');
    }

    public function test_a_longer_chain_of_three_renames_stays_flattened_at_every_step(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'a']);

        $page->update(['slug' => 'b']);
        $page->update(['slug' => 'c']);
        $page->update(['slug' => 'd']);

        foreach (['a', 'b', 'c'] as $old) {
            $this->assertDatabaseHas('redirects', ['from_path' => "/services/{$old}", 'to_path' => '/services/d']);
        }
        $this->assertSame(3, Redirect::query()->count());
    }

    public function test_renaming_a_page_onto_a_path_that_was_previously_a_redirect_source_removes_that_redirect(): void
    {
        Redirect::factory()->create(['from_path' => '/services/reclaimed-slug', 'to_path' => '/services/somewhere-else']);
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'current-slug']);

        $page->update(['slug' => 'reclaimed-slug']);

        $this->assertDatabaseMissing('redirects', ['from_path' => '/services/reclaimed-slug', 'to_path' => '/services/somewhere-else']);
    }

    public function test_changing_the_title_without_changing_the_slug_creates_no_redirect_or_history_row(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'stable-slug', 'title' => 'Old Title']);

        $page->update(['title' => 'New Title']);

        $this->assertSame(0, Redirect::query()->count());
        $this->assertSame(0, SlugHistory::query()->count());
    }

    public function test_setting_the_slug_to_its_own_current_value_is_a_no_op(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'same-slug']);

        $page->slug = 'same-slug';
        $page->save();

        $this->assertSame(0, Redirect::query()->count());
        $this->assertSame(0, SlugHistory::query()->count());
    }

    public function test_a_brand_new_page_being_created_with_its_first_slug_creates_no_redirect(): void
    {
        Page::factory()->create(['type' => PageType::Service, 'slug' => 'first-ever-slug']);

        $this->assertSame(0, Redirect::query()->count());
        $this->assertSame(0, SlugHistory::query()->count());
    }
}
