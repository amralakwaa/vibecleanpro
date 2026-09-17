<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where new-lead notifications go is an operational fact about the one
 * company, so it lives on the business_profiles row next to the public
 * contact email - deliberately as its own column: the public address is
 * what customers write to, this one is the inbox the team actually
 * watches for leads, and the two are often different. Empty means "do
 * not email"; the lead is stored and visible in the panel either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('lead_notification_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn('lead_notification_email');
        });
    }
};
