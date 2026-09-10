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
        Schema::create('internal_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('to_page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('anchor_text')->nullable();
            $table->string('context')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['from_page_id', 'to_page_id']);
            $table->index('to_page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_links');
    }
};
