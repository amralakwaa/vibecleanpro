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
     * @var array<string, string>
     */
    public const APPROVED = [
        'name' => 'Vibe Clean Pro',
        'phone' => '+966534999194',
        'whatsapp_number' => '966534999194',
        'city' => 'الرياض',
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
