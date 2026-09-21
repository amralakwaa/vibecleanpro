<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who promoted a district to Tier A.
 *
 * `promoted_at` and `promotion_reason` already exist; this adds the third
 * part of an accountable promotion - the person. A Tier A page is a claim
 * to Google that a district deserves indexing, so the decision to make it
 * is never anonymous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->foreignId('promoted_by')->nullable()->after('promotion_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promoted_by');
        });
    }
};
