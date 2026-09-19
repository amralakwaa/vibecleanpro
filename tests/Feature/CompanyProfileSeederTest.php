<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_only_empty_fields_and_never_overwrites_editor_data(): void
    {
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000']);

        $this->seed(CompanyProfileSeeder::class);

        $profile = BusinessProfile::query()->sole();
        $this->assertSame('+966500000000', $profile->phone);
        $this->assertSame('966534999194', $profile->whatsapp_number);
        $this->assertSame('https://wa.me/966534999194', $profile->whatsappUrl());
        // Confirmed values are filled in; anything still unconfirmed (a
        // street address, the internal notification inbox) stays empty.
        $this->assertSame('info@vibecleanpro.com', $profile->email);
        $this->assertSame(['كل أيام الأسبوع' => 'من 08:00 إلى 14:00'], $profile->working_hours);
        $this->assertNull($profile->address);
        $this->assertNull($profile->lead_notification_email);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(CompanyProfileSeeder::class);
        $this->seed(CompanyProfileSeeder::class);

        $this->assertSame(1, BusinessProfile::query()->count());
        $this->assertSame('tel:+966534999194', BusinessProfile::query()->sole()->phoneUrl());
    }
}
