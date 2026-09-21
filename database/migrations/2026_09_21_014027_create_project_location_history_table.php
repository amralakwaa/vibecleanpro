<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An audit trail for where a case study says the work happened.
 *
 * A location that powers local SEO must be answerable: who set it, from
 * what source, and what it was before. This records every change to the
 * place fields - area, city, neighbourhood, landmark - so "why does this
 * project claim Al-Yasmin?" always has an answer that is not memory.
 *
 * old_location and new_location are JSON snapshots of the place fields,
 * so the row explains itself even after the columns change again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_location_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->json('old_location')->nullable();
            $table->json('new_location');
            $table->string('source')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_location_history');
    }
};
