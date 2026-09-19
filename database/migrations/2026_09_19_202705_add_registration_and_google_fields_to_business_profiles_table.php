<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Only what the profile did not already have: the public email
     * (`email`), the lead notification inbox (`lead_notification_email`)
     * and the opening hours (`working_hours`) exist already. The commercial
     * registration is shown publicly only when the owner turns that on.
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('commercial_registration_number', 30)->nullable()->after('city');
            $table->boolean('display_commercial_registration')->default(false)->after('commercial_registration_number');
            $table->text('service_area')->nullable()->after('display_commercial_registration');
            $table->string('google_business_profile_url', 500)->nullable()->after('social_links');
            $table->string('google_review_url', 500)->nullable()->after('google_business_profile_url');
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'commercial_registration_number', 'display_commercial_registration', 'service_area',
                'google_business_profile_url', 'google_review_url',
            ]);
        });
    }
};
