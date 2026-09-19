<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use Illuminate\Database\Seeder;

/**
 * The company data already approved in the project documents (NAP used
 * across every page draft and schema). Only fills fields that are still
 * empty - anything an editor has entered in the admin panel wins, so this
 * is safe to re-run. Unconfirmed data (email, address, hours, founder) is
 * deliberately left empty: an empty field is hidden on the public site,
 * a guessed one would be published.
 */
class CompanyProfileSeeder extends Seeder
{
    /**
     * Owner-confirmed values. `working_hours` keeps 24-hour times so the
     * structured data generator can read them unambiguously; the label is
     * what visitors see. The lead notification inbox is deliberately NOT
     * here - it is an internal address the owner decides separately.
     *
     * @var array<string, string|array<string, string>>
     */
    public const APPROVED = [
        'name' => 'Vibe Clean Pro',
        'phone' => '+966534999194',
        'whatsapp_number' => '966534999194',
        'city' => 'الرياض',
        'email' => 'info@vibecleanpro.com',
        'service_area' => 'مدينة الرياض',
        'working_hours' => ['كل أيام الأسبوع' => 'من 08:00 إلى 14:00'],
    ];

    public function run(): void
    {
        // The site has exactly one profile, whatever its id - never create a
        // second one next to an existing row.
        $profile = BusinessProfile::query()->first() ?? new BusinessProfile;

        foreach (self::APPROVED as $field => $value) {
            if (blank($profile->{$field})) {
                $profile->{$field} = $value;
            }
        }

        $profile->save();
    }
}
