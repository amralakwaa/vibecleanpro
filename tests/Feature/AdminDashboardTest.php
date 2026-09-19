<?php

namespace Tests\Feature;

use App\Enums\ConversionEventType;
use App\Filament\Widgets\ConversionBreakdownWidget;
use App\Filament\Widgets\ConversionStatsWidget;
use App\Filament\Widgets\LaunchReadinessWidget;
use App\Models\ConversionEvent;
use App\Models\Service;
use App\Models\User;
use App\Support\Tracking\ConversionReport;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function actingAsRole(string $role): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
    }

    public function test_the_report_counts_only_the_window_and_ranks_services(): void
    {
        $villa = Service::factory()->create(['name' => 'تنظيف الفلل']);
        $office = Service::factory()->create(['name' => 'تنظيف المكاتب']);
        ConversionEvent::factory()->count(3)->create(['event_type' => ConversionEventType::WhatsappClick, 'service_id' => $villa->id, 'source' => 'gbp']);
        ConversionEvent::factory()->create(['event_type' => ConversionEventType::PhoneClick, 'service_id' => $office->id, 'source' => 'organic_search']);
        ConversionEvent::factory()->create(['event_type' => ConversionEventType::WhatsappClick, 'service_id' => $office->id, 'created_at' => now()->subDays(45)]);

        $report = new ConversionReport(30);

        $this->assertSame(3, $report->totals()[ConversionEventType::WhatsappClick->value]);
        $this->assertSame(1, $report->totals()[ConversionEventType::PhoneClick->value]);
        $this->assertSame(['name' => 'تنظيف الفلل', 'total' => 3], $report->topServices()->first());
        $this->assertSame('gbp', $report->bySource()->first()['name']);
    }

    public function test_the_dashboard_renders_the_new_widgets_for_an_administrator(): void
    {
        $this->actingAsRole('Administrator');
        ConversionEvent::factory()->create(['event_type' => ConversionEventType::WhatsappClick]);

        $this->get('/admin')->assertOk();
        Livewire::test(ConversionStatsWidget::class)->assertOk()->assertSee('نقرات واتساب');
        Livewire::test(ConversionBreakdownWidget::class)->assertOk()->assertSee('أكثر الخدمات تحويلًا');
        Livewire::test(LaunchReadinessWidget::class)->assertOk()->assertSee('مشاريع بانتظار تأكيد المالك');
    }

    public function test_conversion_reports_are_hidden_from_roles_without_the_permission(): void
    {
        $this->actingAsRole('Content Manager');

        $this->assertFalse(ConversionStatsWidget::canView());
        $this->assertFalse(ConversionBreakdownWidget::canView());
        $this->assertFalse(LaunchReadinessWidget::canView());
    }
}
