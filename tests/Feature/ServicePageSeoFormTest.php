<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Page;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Covers the riskiest custom mechanism in the CMS: a Service's admin form
 * teleports fields into its Page (morphOne) and that Page's SeoMetadata
 * (hasOne) via nested Filament relationship() sections. This exercises the
 * real Livewire component, not just the underlying Eloquent relations.
 */
class ServicePageSeoFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($admin);
    }

    public function test_creating_a_service_creates_its_page_and_seo_metadata_and_keeps_the_manual_slug(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([
                'name' => 'صيانة وتنظيف السجاد',
                'page.title' => 'صيانة وتنظيف السجاد بالرياض',
                'page.slug' => 'carpet-cleaning-riyadh',
                'page.status' => PageStatus::Draft->value,
                'page.seoMetadata.meta_title' => 'تنظيف السجاد بالرياض | Vibe Clean Pro',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $service = Service::query()->where('name', 'صيانة وتنظيف السجاد')->firstOrFail();

        $this->assertNotNull($service->page, 'Service was not linked to a Page.');
        $this->assertSame('carpet-cleaning-riyadh', $service->page->slug, 'Manual slug was overwritten by auto-generation.');
        $this->assertSame(PageStatus::Draft, $service->page->status);

        $this->assertNotNull($service->page->seoMetadata, 'Page was not linked to SeoMetadata.');
        $this->assertSame('تنظيف السجاد بالرياض | Vibe Clean Pro', $service->page->seoMetadata->meta_title);

        $this->assertSame(1, Page::query()->count());
        $this->assertSame(1, SeoMetadata::query()->count());
    }

    public function test_editing_a_service_updates_the_existing_page_and_seo_metadata_without_duplicating_them(): void
    {
        $service = Service::factory()->create(['name' => 'اسم قديم']);
        $page = Page::factory()->create(['title' => 'عنوان قديم', 'status' => PageStatus::Draft]);
        $service->page()->save($page);
        $seo = SeoMetadata::factory()->for($page)->create(['meta_title' => 'قديم']);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'name' => 'اسم محدث',
                'page.title' => 'عنوان محدث',
                'page.status' => PageStatus::Draft->value,
                'page.seoMetadata.meta_title' => 'عنوان SEO محدث',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $service->refresh();

        $this->assertSame('اسم محدث', $service->name);
        $this->assertSame($page->id, $service->page->id, 'Editing created a second Page instead of updating the existing one.');
        $this->assertSame('عنوان محدث', $service->page->title);
        $this->assertSame(PageStatus::Draft, $service->page->status);

        $this->assertSame($seo->id, $service->page->seoMetadata->id, 'Editing created a second SeoMetadata row instead of updating the existing one.');
        $this->assertSame('عنوان SEO محدث', $service->page->seoMetadata->meta_title);

        $this->assertSame(1, Page::query()->count());
        $this->assertSame(1, SeoMetadata::query()->count());
    }
}
