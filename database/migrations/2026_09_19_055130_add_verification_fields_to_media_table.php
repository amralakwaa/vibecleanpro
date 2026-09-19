<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('status', 10)->default('pending')->after('variants');
            $table->string('privacy_status', 30)->default('unverified')->after('status');
            $table->string('media_type', 12)->default('real')->after('privacy_status');
            $table->string('source', 20)->nullable()->after('media_type');
            $table->string('source_original_name')->nullable()->after('original_filename');
            $table->string('source_group', 60)->nullable()->after('source');
            $table->text('verified_description')->nullable();
            $table->string('captured_stage', 10)->nullable();
            $table->date('captured_at')->nullable();
            $table->string('consent_ref')->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();

            $table->index(['status', 'privacy_status']);
            $table->index('source_group');
            $table->index('content_hash');
        });

        // The existing licensed stock illustrations (InitialMediaSeeder) are
        // cleared for generic visuals - and marked as stock, so they can
        // never be used as evidence of the company's own work.
        DB::table('media')->where('path', 'like', 'media/library/%')->update([
            'status' => 'ready',
            'privacy_status' => 'cleared',
            'media_type' => 'stock',
            'source' => 'stock_library',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['area_id']);
            $table->dropIndex(['status', 'privacy_status']);
            $table->dropIndex(['source_group']);
            $table->dropIndex(['content_hash']);
            $table->dropColumn([
                'status', 'privacy_status', 'media_type', 'source', 'source_original_name', 'source_group',
                'verified_description', 'captured_stage', 'captured_at', 'consent_ref', 'content_hash',
                'service_id', 'area_id',
            ]);
        });
    }
};
