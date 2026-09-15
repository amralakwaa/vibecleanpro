<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * The pricing form refuses nonsense (a fixed price with no number, a
 * range upside-down, a per-unit price with no unit, negative money) and
 * asks nothing of a quote-only service - through the real Filament
 * screens, since that is the only place prices are ever entered.
 */
class ServicePricingAdminTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($admin);
    }

    public function test_a_quote_only_service_saves_without_any_price(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('quote-only'), 'name' => 'خدمة بلا سعر', 'pricing_mode' => 'quote_only'])
            ->call('create')
            ->assertHasNoFormErrors();

        $service = Service::query()->where('name', 'خدمة بلا سعر')->firstOrFail();
        $this->assertSame('quote_only', $service->pricing_mode->value);
        $this->assertNull($service->price_min);
        $this->assertNull($service->publicPrice());
    }

    public function test_a_fixed_price_requires_a_positive_number(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('fixed-missing'), 'name' => 'ثابت بلا رقم', 'pricing_mode' => 'fixed', 'price_min' => null])
            ->call('create')
            ->assertHasFormErrors(['price_min' => 'required']);

        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('fixed-negative'), 'name' => 'ثابت سالب', 'pricing_mode' => 'fixed', 'price_min' => -50])
            ->call('create')
            ->assertHasFormErrors(['price_min']);

        $this->assertSame(0, Service::query()->count());
    }

    public function test_a_range_needs_both_ends_and_the_right_way_round(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('range-missing'), 'name' => 'نطاق ناقص', 'pricing_mode' => 'range', 'price_min' => 300, 'price_max' => null])
            ->call('create')
            ->assertHasFormErrors(['price_max' => 'required']);

        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('range-inverted'), 'name' => 'نطاق مقلوب', 'pricing_mode' => 'range', 'price_min' => 500, 'price_max' => 300])
            ->call('create')
            ->assertHasFormErrors(['price_max']);

        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('range-ok'), 'name' => 'نطاق صحيح', 'pricing_mode' => 'range', 'price_min' => 300, 'price_max' => 500])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('من 300 إلى 500 ر.س', Service::query()->where('name', 'نطاق صحيح')->firstOrFail()->publicPrice()->label());
    }

    public function test_a_per_unit_price_requires_its_unit(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('unit-missing'), 'name' => 'وحدة ناقصة', 'pricing_mode' => 'per_unit', 'price_min' => 20, 'price_unit' => ''])
            ->call('create')
            ->assertHasFormErrors(['price_unit' => 'required']);

        Livewire::test(CreateService::class)
            ->fillForm([...$this->pageFields('unit-ok'), 'name' => 'وحدة صحيحة', 'pricing_mode' => 'per_unit', 'price_min' => 20, 'price_unit' => 'متر'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('20 ر.س / متر', Service::query()->where('name', 'وحدة صحيحة')->firstOrFail()->publicPrice()->label());
    }

    public function test_switching_back_to_quote_only_clears_the_numbers_from_the_public_site(): void
    {
        $service = $this->createCompliantServicePage(slug: 'switch-back')->pageable;
        $service->update(['pricing_mode' => 'starting_from', 'price_min' => 299]);
        $this->assertStringContainsString('يبدأ من 299 ر.س', $this->get('/services/switch-back')->getContent());

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm(['pricing_mode' => 'quote_only', 'page.status' => PageStatus::Published->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($service->fresh()->publicPrice());
        $this->assertStringNotContainsString('يبدأ من 299 ر.س', $this->get('/services/switch-back')->getContent());
    }

    /**
     * @return array<string, mixed>
     */
    private function pageFields(string $slug): array
    {
        return [
            'page.title' => 'صفحة '.$slug,
            'page.slug' => $slug,
            'page.status' => PageStatus::Draft->value,
        ];
    }
}
