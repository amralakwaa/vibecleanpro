<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case-study fields. All nullable on purpose: a project carries only
     * what is actually known about it, and every section the owner has not
     * filled simply does not render. Nothing is ever auto-written here -
     * the problem, the site condition, the steps and the outcome are
     * statements about real work, so only a human may write them.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('challenge')->nullable()->after('summary');
            $table->text('site_condition')->nullable()->after('challenge');
            $table->json('execution_steps')->nullable()->after('site_condition');
            $table->text('outcome')->nullable()->after('execution_steps');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['challenge', 'site_condition', 'execution_steps', 'outcome']);
        });
    }
};
