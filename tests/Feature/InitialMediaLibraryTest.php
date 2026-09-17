<?php

namespace Tests\Feature;

use App\Enums\MediaStage;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Support\MediaPicker;
use App\Models\BusinessProfile;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectMedia;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\InitialMediaSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * The initial media library is production content, not test data: every
 * file is a licensed illustration with a manifest entry, imported into the
 * real Media architecture, editable from Filament, reproducible after a
 * fresh install - and never presented as the company's own project work.
 */
class InitialMediaLibraryTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_every_manifest_item_is_a_licensed_local_file_with_natural_alt_text(): void
    {
        $manifest = InitialMediaSeeder::manifest();

        $this->assertCount(12, $manifest['items']);

        foreach ($manifest['items'] as $item) {
            $file = InitialMediaSeeder::sourceDirectory().'/'.$item['file'];
            $this->assertFileExists($file, $item['key']);
            $this->assertLessThan(600 * 1024, filesize($file), $item['key'].' should stay web-sized');
            $this->assertNotFalse(getimagesize($file), $item['key'].' must be a readable image');

            foreach (['platform', 'license', 'url', 'photographer'] as $field) {
                $this->assertNotEmpty($item['source'][$field] ?? null, "{$item['key']} is missing source.{$field}");
            }
            $this->assertStringStartsWith('https://www.pexels.com/', $item['source']['url']);
            $this->assertStringContainsString('commercial', $item['source']['license']);

            // Alt text describes the picture - never a keyword string.
            $this->assertNotEmpty($item['alt_text']);
            $this->assertDoesNotMatchRegularExpression('/أفضل شركة|بالرياض|شركة تنظيف/u', $item['alt_text'], $item['key']);
        }
    }

    public function test_the_seeder_imports_the_library_once_and_keeps_editor_changes(): void
    {
        $this->seed(InitialMediaSeeder::class);

        $this->assertSame(12, Media::query()->count());
        Media::query()->each(function (Media $media) {
            Storage::disk('public')->assertExists($media->path);
            $this->assertTrue($media->isLibraryStock());
            $this->assertNotNull($media->width);
            $this->assertNotNull($media->height);
            $this->assertNotEmpty($media->alt_text);
        });

        $hero = Media::query()->where('path', Media::LIBRARY_DIRECTORY.'/home-hero.jpg')->firstOrFail();
        $hero->update(['alt_text' => 'نص-بديل-عدّله-المحرر']);

        $this->seed(InitialMediaSeeder::class);

        $this->assertSame(12, Media::query()->count());
        $this->assertSame('نص-بديل-عدّله-المحرر', $hero->fresh()->alt_text);
        $this->assertSame((string) $hero->id, (string) SiteSetting::get(SiteSetting::HOME_HERO_MEDIA_ID));
    }

    public function test_services_without_an_image_get_the_matching_library_image_and_nothing_else_changes(): void
    {
        $bySlug = $this->createCompliantServicePage(slug: 'villa-cleaning')->pageable;
        $bySlug->update(['featured_media_id' => null]);
        $byName = $this->createCompliantServicePage(slug: 'some-other-slug')->pageable;
        $byName->update(['name' => 'تنظيف المكيفات المركزية', 'featured_media_id' => null]);
        $keepsOwn = $this->createCompliantServicePage(slug: 'apartment-cleaning')->pageable;
        $ownImageId = $keepsOwn->featured_media_id;
        $unmatched = $this->createCompliantServicePage(slug: 'unknown-service')->pageable;
        $unmatched->update(['name' => 'خدمة بلا تصنيف', 'featured_media_id' => null]);

        $this->seed(InitialMediaSeeder::class);

        $this->assertSame(Media::LIBRARY_DIRECTORY.'/villa-cleaning.jpg', $bySlug->fresh()->featuredMedia->path);
        $this->assertSame(Media::LIBRARY_DIRECTORY.'/ac-cleaning.jpg', $byName->fresh()->featuredMedia->path);
        $this->assertSame($ownImageId, $keepsOwn->fresh()->featured_media_id, 'an existing image is never replaced');
        $this->assertNull($unmatched->fresh()->featured_media_id, 'no guess for a service the library does not cover');
    }

    public function test_no_library_stock_is_ever_attached_to_a_project(): void
    {
        $project = Project::factory()->create();
        $realPhoto = Media::factory()->create(['alt_text' => 'صورة حقيقية']);
        $project->media()->attach($realPhoto->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);

        $this->seed(InitialMediaSeeder::class);

        $this->assertSame(1, ProjectMedia::query()->count());
        $this->assertSame(0, ProjectMedia::query()->whereIn('media_id', Media::query()->where('path', 'like', Media::LIBRARY_DIRECTORY.'/%')->pluck('id'))->count());

        // The Filament project picker does not even offer the stock files.
        $offered = MediaPicker::makeForProject('media_id')->getSearchResults('');
        $this->assertArrayHasKey($realPhoto->id, $offered);
        foreach (array_keys($offered) as $offeredId) {
            $this->assertFalse(Media::query()->findOrFail($offeredId)->isLibraryStock(), "stock media {$offeredId} offered to a project");
        }
        $this->assertArrayHasKey($realPhoto->id, MediaPicker::make('media_id')->getSearchResults(''), 'the general picker still offers everything');
    }

    public function test_an_admin_swaps_the_hero_image_from_site_settings_and_the_homepage_follows(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض']);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InitialMediaSeeder::class);
        $replacement = Media::factory()->create(['alt_text' => 'صورة-بديلة-من-الإدارة']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        Livewire::test(ManageSiteSettings::class)
            ->assertFormSet([SiteSetting::HOME_HERO_MEDIA_ID => Media::query()->where('path', Media::LIBRARY_DIRECTORY.'/home-hero.jpg')->value('id')])
            ->fillForm([SiteSetting::HOME_HERO_MEDIA_ID => $replacement->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($replacement->id, SiteSetting::get(SiteSetting::HOME_HERO_MEDIA_ID));
        $this->assertStringContainsString('src="'.$replacement->url().'"', $this->get('/')->assertOk()->getContent());
    }

    public function test_the_homepage_serves_the_library_hero_and_service_images_from_local_storage_only(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض']);
        $service = $this->createCompliantServicePage(slug: 'villa-cleaning')->pageable;
        $service->update(['featured_media_id' => null, 'is_featured' => true]);
        $this->seed(InitialMediaSeeder::class);

        $html = $this->get('/')->assertOk()->getContent();

        $heroUrl = Media::query()->find(SiteSetting::get(SiteSetting::HOME_HERO_MEDIA_ID))->url();
        $this->assertStringContainsString('src="'.$heroUrl.'"', $html);
        $this->assertStringContainsString('src="'.$service->fresh()->featuredMedia->url().'"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);

        preg_match_all('/<img\b[^>]*>/u', $html, $images);
        $this->assertNotEmpty($images[0]);
        foreach ($images[0] as $tag) {
            $this->assertMatchesRegularExpression('/\balt="/u', $tag, $tag);
            $this->assertMatchesRegularExpression('/\bwidth="\d+"/u', $tag, $tag);
            $this->assertMatchesRegularExpression('/\bheight="\d+"/u', $tag, $tag);
            $this->assertDoesNotMatchRegularExpression('#src="https?://(?!'.preg_quote(parse_url(config('app.url'), PHP_URL_HOST), '#').')#u', $tag, 'no hotlinked image: '.$tag);
        }
        $this->assertDoesNotMatchRegularExpression('/pexels\.com|unsplash\.com|images\.unsplash|cdn\./u', $html);
    }

    public function test_the_homepage_still_works_when_the_hero_image_is_removed_and_falls_back_to_project_photos(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'city' => 'الرياض']);
        $this->seed(InitialMediaSeeder::class);

        Media::query()->where('path', Media::LIBRARY_DIRECTORY.'/home-hero.jpg')->delete();
        $this->get('/')->assertOk();
        $this->assertStringNotContainsString('fetchpriority="high"', $this->get('/')->getContent(), 'no hero photograph is invented');

        SiteSetting::query()->where('key', SiteSetting::HOME_HERO_MEDIA_ID)->delete();
        $project = $this->createCompliantServicePage(slug: 'fallback-project-svc')->pageable->projects->first();
        $after = Media::factory()->create(['alt_text' => 'بعد التنفيذ']);
        $project->media()->attach($after->id, ['stage' => MediaStage::After->value, 'sort_order' => 0]);
        $project->update(['is_featured' => true]);
        $page = Page::factory()->create(['type' => PageType::Project, 'slug' => 'fallback-project']);
        $project->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'نص.']]);
        $page->update(['status' => PageStatus::Published]);

        $this->assertStringContainsString('src="'.$after->url().'"', $this->get('/')->assertOk()->getContent());
    }
}
