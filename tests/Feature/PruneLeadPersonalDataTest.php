<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneLeadPersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clears_old_technical_data_and_deletes_old_spam_only(): void
    {
        $old = Lead::factory()->create(['ip_address' => '1.1.1.1', 'user_agent' => 'UA', 'created_at' => now()->subDays(91)]);
        $recent = Lead::factory()->create(['ip_address' => '2.2.2.2', 'user_agent' => 'UA', 'created_at' => now()->subDays(10)]);
        $oldSpam = Lead::factory()->create(['status' => LeadStatus::Spam, 'created_at' => now()->subDays(31)]);
        $newSpam = Lead::factory()->create(['status' => LeadStatus::Spam, 'created_at' => now()->subDays(5)]);

        $this->artisan('leads:prune-personal-data')->assertSuccessful();

        $this->assertNull($old->fresh()->ip_address);
        $this->assertNull($old->fresh()->user_agent);
        $this->assertSame('2.2.2.2', $recent->fresh()->ip_address);
        $this->assertNull(Lead::withTrashed()->find($oldSpam->id));
        $this->assertNotNull(Lead::withTrashed()->find($newSpam->id));
        $this->assertSame($old->name, $old->fresh()->name);
    }

    public function test_it_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('leads:prune-personal-data')->assertSuccessful();
    }
}
