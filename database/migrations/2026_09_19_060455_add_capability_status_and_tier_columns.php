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
        Schema::table('services', function (Blueprint $table) {
            $table->string('capability_status', 20)->default('available')->after('pricing_mode');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->char('tier', 1)->default('a')->after('area_group_id');
            $table->timestamp('promoted_at')->nullable()->after('tier');
            $table->string('promotion_reason')->nullable()->after('promoted_at');
            $table->index('tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropIndex(['tier']);
            $table->dropColumn(['tier', 'promoted_at', 'promotion_reason']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('capability_status');
        });
    }
};
