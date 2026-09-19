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
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('area_id')->constrained('projects')->nullOnDelete();
            $table->string('device_type', 10)->nullable()->after('user_agent');
            $table->string('attribution_code', 12)->nullable()->after('source');
            $table->string('lost_reason', 20)->nullable()->after('assigned_to');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('review_requested_at')->nullable();
            $table->boolean('consent_marketing')->default(false);

            $table->index(['source', 'created_at']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Foreign keys first: MySQL uses the (assigned_to, status) index to
            // back the assigned_to foreign key and refuses to drop it while the
            // key exists - and DDL is not transactional, so order matters.
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['project_id']);
            $table->dropIndex(['source', 'created_at']);
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropColumn([
                'assigned_to', 'project_id',
                'device_type', 'attribution_code', 'lost_reason', 'contacted_at', 'quoted_at',
                'completed_at', 'review_requested_at', 'consent_marketing',
            ]);
        });
    }
};
