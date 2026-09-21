<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the SEO priority so the panel can sort by it, and gives every
 * project a cluster and a focus keyword.
 *
 * The score stays a calculation - ProjectSeoPriority is still the only
 * place that decides it. These columns are a snapshot with a timestamp,
 * so an editor can sort a table by priority and see how old that ranking
 * is. `php artisan projects:seo-priority --store` refreshes them.
 *
 * Deliberately NOT added: seo_title and seo_description. Those already
 * exist as seo_metadata.meta_title / meta_description against the
 * project's page - that is what the <head> and the structured data read,
 * and it is already editable in the project form. Duplicating them here
 * would create two sources of truth and a column nothing renders.
 * `focus_keyword` is different: nothing stores it today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedTinyInteger('seo_priority_score')->nullable()->after('sort_order');
            $table->string('seo_priority_tier', 1)->nullable()->after('seo_priority_score');
            $table->timestamp('seo_priority_updated_at')->nullable()->after('seo_priority_tier');

            $table->string('focus_keyword')->nullable()->after('summary');
            $table->string('cluster', 40)->nullable()->after('focus_keyword');

            $table->index(['seo_priority_tier', 'seo_priority_score']);
            $table->index('cluster');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['seo_priority_tier', 'seo_priority_score']);
            $table->dropIndex(['cluster']);
            $table->dropColumn(['seo_priority_score', 'seo_priority_tier', 'seo_priority_updated_at', 'focus_keyword', 'cluster']);
        });
    }
};
