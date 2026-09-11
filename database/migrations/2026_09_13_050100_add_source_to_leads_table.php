<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Which public form/channel this lead came from - e.g.
            // "quote_form", "contact_form". A short free-form string
            // (not an enum) since new entry channels can appear without a
            // migration; the admin Lead list is where these are read.
            $table->string('source')->nullable()->after('landing_page');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
