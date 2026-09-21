<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A case study already records what we faced and what we did. This adds
 * the part a buyer actually reads: why the job mattered to the client.
 *
 * It sits beside `outcome` rather than inside it on purpose - outcome is
 * what the photographs show, client_value is what it means for someone
 * deciding whether to call.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('client_value')->nullable()->after('outcome');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('client_value');
        });
    }
};
