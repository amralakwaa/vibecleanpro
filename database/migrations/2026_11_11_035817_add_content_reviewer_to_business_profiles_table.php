<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The person who reviews the technical content of the service pages. Shown
 * as one line on every service page and as `reviewedBy` in its WebPage
 * structured data; both disappear while the name is empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('content_reviewer_name', 150)->nullable()->after('founder_long_bio');
            $table->string('content_reviewer_title', 150)->nullable()->after('content_reviewer_name');
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn(['content_reviewer_name', 'content_reviewer_title']);
        });
    }
};
