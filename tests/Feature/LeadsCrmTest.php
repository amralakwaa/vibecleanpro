<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use App\Models\User;
use App\Support\Tracking\AttributionCode;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class LeadsCrmTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_the_lead_screens_render_for_an_administrator(): void
    {
        $this->actingAsRole('Administrator');
        $lead = Lead::factory()->create();

        Livewire::test(ListLeads::class)->assertOk();
        Livewire::test(CreateLead::class)->assertOk();
        Livewire::test(EditLead::class, ['record' => $lead->getRouteKey()])->assertOk();
    }

    public function test_a_whatsapp_chat_logged_with_its_page_code_is_attributed_to_that_page(): void
    {
        $page = $this->createCompliantServicePage(slug: 'villa-cleaning');
        $this->actingAsRole('Sales');
        $code = AttributionCode::forPath('/services/villa-cleaning');

        Livewire::test(CreateLead::class)
            ->fillForm([
                'name' => 'عميل واتساب',
                'phone' => '0533333333',
                'status' => LeadStatus::New->value,
                'source' => 'whatsapp',
                'attribution_code' => strtolower(substr($code, 2)),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $lead = Lead::query()->sole();
        $this->assertSame('whatsapp', $lead->source);
        $this->assertSame($code, $lead->attribution_code);
        $this->assertSame($page->id, $lead->source_page_id);
        $this->assertSame($page->pageable_id, $lead->service_id);
    }

    public function test_an_unknown_code_is_kept_but_attributes_nothing(): void
    {
        $lead = Lead::factory()->create(['attribution_code' => 'V-ZZZZZZZ']);

        $this->assertNull($lead->source_page_id);
        $this->assertSame('V-ZZZZZZZ', $lead->attribution_code);
    }

    public function test_each_pipeline_stage_is_stamped_once_including_skipped_stages(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::New]);
        $this->assertNull($lead->contacted_at);

        $lead->update(['status' => LeadStatus::Quoted]);
        $this->assertNotNull($lead->contacted_at);
        $this->assertNotNull($lead->quoted_at);
        $firstQuote = $lead->quoted_at;

        $this->travel(2)->days();
        $lead->update(['status' => LeadStatus::Completed]);

        $this->assertNotNull($lead->completed_at);
        $this->assertTrue($firstQuote->equalTo($lead->quoted_at));
    }

    public function test_a_lost_lead_requires_a_reason(): void
    {
        $this->actingAsRole('Administrator');
        $lead = Lead::factory()->create();

        Livewire::test(EditLead::class, ['record' => $lead->getRouteKey()])
            ->fillForm(['status' => LeadStatus::Lost->value, 'lost_reason' => null])
            ->call('save')
            ->assertHasFormErrors(['lost_reason' => 'required']);

        Livewire::test(EditLead::class, ['record' => $lead->getRouteKey()])
            ->fillForm(['status' => LeadStatus::Lost->value, 'lost_reason' => 'price'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('price', $lead->fresh()->lost_reason->value);
    }

    public function test_a_salesperson_sees_only_their_own_and_unassigned_leads(): void
    {
        $sales = $this->actingAsRole('Sales');
        $colleague = User::factory()->create();

        $mine = Lead::factory()->create(['assigned_to' => $sales->id]);
        $unassigned = Lead::factory()->create(['assigned_to' => null]);
        $theirs = Lead::factory()->create(['assigned_to' => $colleague->id]);

        Livewire::test(ListLeads::class)
            ->assertCanSeeTableRecords([$mine, $unassigned])
            ->assertCanNotSeeTableRecords([$theirs]);

        $this->get(route('filament.admin.resources.leads.edit', $theirs))->assertNotFound();
    }

    public function test_an_administrator_sees_every_lead(): void
    {
        $this->actingAsRole('Administrator');
        $leads = Lead::factory()->count(2)->create(['assigned_to' => User::factory()->create()->id]);

        Livewire::test(ListLeads::class)->assertCanSeeTableRecords($leads);
    }

    public function test_a_form_lead_records_the_device(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) Mobile Safari/537.36')
            ->post('/quote', ['name' => 'جوال', 'phone' => '0544444444'])
            ->assertSessionHasNoErrors();

        $this->assertSame('mobile', Lead::query()->sole()->device_type);
    }

    public function test_every_status_has_a_label_and_a_colour(): void
    {
        foreach (LeadStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
            $this->assertNotSame('', $status->color());
        }

        $this->assertArrayHasKey('completed', LeadStatus::options());
    }
}
