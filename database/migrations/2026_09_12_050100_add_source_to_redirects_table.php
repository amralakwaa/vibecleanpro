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
        Schema::table('redirects', function (Blueprint $table) {
            // 'slug_change' rows are written by the slug lifecycle system and
            // kept in sync automatically (flattened, repointed); 'manual' rows
            // are edited freely by an SEO Manager. Distinguishing them in the
            // UI prevents an admin from hand-editing a row the system expects
            // to own.
            $table->string('source')->default('manual')->after('to_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
