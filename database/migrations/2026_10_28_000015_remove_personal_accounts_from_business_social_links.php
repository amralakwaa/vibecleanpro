<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The owner confirmed (2026-10-28) that these two handles are personal, not
 * the business's. They are dropped from the site and from LocalBusiness
 * sameAs; the business accounts will be entered from the admin panel.
 * A link already replaced in the admin is never touched.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const PERSONAL = [
        'snapchat' => 'https://www.snapchat.com/add/eng_amr967',
        'instagram' => 'https://www.instagram.com/alakw_aa',
    ];

    public function up(): void
    {
        $profile = DB::table('business_profiles')->orderBy('id')->first();

        if ($profile === null) {
            return;
        }

        $links = json_decode((string) $profile->social_links, true) ?: [];
        $kept = array_filter($links, fn ($url, $platform) => (self::PERSONAL[$platform] ?? null) !== $url, ARRAY_FILTER_USE_BOTH);

        if ($kept !== $links) {
            DB::table('business_profiles')->where('id', $profile->id)->update(['social_links' => json_encode($kept, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $profile = DB::table('business_profiles')->orderBy('id')->first();

        if ($profile === null) {
            return;
        }

        $links = json_decode((string) $profile->social_links, true) ?: [];
        $restored = $links + array_diff_key(self::PERSONAL, $links);

        DB::table('business_profiles')->where('id', $profile->id)->update(['social_links' => json_encode($restored, JSON_UNESCAPED_UNICODE)]);
    }
};
