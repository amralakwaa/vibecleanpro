<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The trust points shown on the homepage, About and every service page
     * (Saudi team, accreditation, warranty). One editable source, so the
     * same promise is never retyped into page content and cannot drift.
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->json('trust_points')->nullable()->after('values');
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn('trust_points');
        });
    }
};
