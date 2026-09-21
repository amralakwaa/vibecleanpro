<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why a case study's priority moved, and when.
 *
 * A tier that changes silently is a tier nobody trusts. This records the
 * moment it moved and the reason, so "why is this project suddenly Tier
 * A?" has an answer that is not someone's memory.
 *
 * A row is written ONLY when the score or the tier actually changed.
 * Re-running the command on an unchanged project writes nothing - a log
 * full of no-op entries hides the three that matter.
 *
 * `axes` keeps the five sub-scores at the time of the change, which is
 * what lets the NEXT change explain itself: without them the reason could
 * only say "it went up", never "media strength rose after four photos
 * were added".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_seo_priority_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('old_score')->nullable();
            $table->unsignedTinyInteger('new_score');
            $table->string('old_tier', 1)->nullable();
            $table->string('new_tier', 1);
            $table->text('reason');
            $table->json('axes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_seo_priority_history');
    }
};
