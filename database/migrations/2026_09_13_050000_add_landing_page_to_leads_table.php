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
            // The public path the visitor first landed on (e.g.
            // "/services/carpet-cleaning") - distinct from source_page_id,
            // which only applies to typed CMS Pages. A lead started from
            // the homepage or straight from /quote still has one.
            $table->string('landing_page')->nullable()->after('source_page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('landing_page');
        });
    }
};
