<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team members are a real list (add / edit / reorder / hide), unlike the
 * founder, so they get their own table instead of a JSON column on the
 * business profile. Hiding is is_active, never deletion, so a member on
 * leave can be brought back without re-entering their data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role_title')->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('bio')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // The one public read: WHERE is_active = 1 ORDER BY sort_order.
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
