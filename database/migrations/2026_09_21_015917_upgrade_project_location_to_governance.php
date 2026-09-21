<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the location layer from a single verified flag into a governed
 * workflow.
 *
 * Adds a confidence ladder (0-4), a place to record what the evidence
 * actually is, and a review status (draft / pending_review / verified /
 * rejected) that replaces the boolean. The old boolean is migrated, not
 * dropped blindly: a true became verified, everything else draft - which
 * in practice is all of them, since nothing was verified yet.
 *
 * The SEO gate now reads status + confidence, so the boolean's index is
 * replaced by indexes on the two columns the gate filters on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedTinyInteger('location_confidence')->default(0)->after('location_source');
            $table->string('location_evidence_reference')->nullable()->after('location_confidence');
            $table->string('location_status')->default('draft')->after('location_evidence_reference');
        });

        // Carry the old boolean across before removing it.
        DB::table('projects')->where('location_verified', true)->update([
            'location_status' => 'verified',
            'location_confidence' => 4,
        ]);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['location_verified']);
            $table->dropColumn('location_verified');
            $table->index('location_status');
            $table->index('location_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('location_verified')->default(false)->after('location_source');
        });

        DB::table('projects')->where('location_status', 'verified')->update(['location_verified' => true]);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['location_status']);
            $table->dropIndex(['location_confidence']);
            $table->index('location_verified');
            $table->dropColumn(['location_confidence', 'location_evidence_reference', 'location_status']);
        });
    }
};
