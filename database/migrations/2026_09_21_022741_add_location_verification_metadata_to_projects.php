<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The accountability around a verified location: what kind of proof backs
 * it, who signed it off, and when.
 *
 * `location_evidence_type` records the artefact (contract, invoice, photo
 * metadata...), sitting beside the existing `location_evidence_reference`
 * which points at the specific file or document. `verified_by` and
 * `verified_at` are stamped by the observer the moment status becomes
 * verified, so "who claimed this district, and on what, and when" always
 * has an answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('location_evidence_type')->nullable()->after('location_source');
            $table->timestamp('verified_at')->nullable()->after('location_status');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['location_evidence_type', 'verified_at']);
        });
    }
};
