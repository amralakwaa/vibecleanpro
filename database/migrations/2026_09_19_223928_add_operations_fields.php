<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operations centre additions. Integration settings themselves live in
     * the existing site_settings key/value table (secrets encrypted), so
     * only these two columns are needed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('google_maps_place_id', 255)->nullable()->after('google_review_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn('google_maps_place_id');
        });
    }
};
