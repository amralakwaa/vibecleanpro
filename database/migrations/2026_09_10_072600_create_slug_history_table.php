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
        Schema::create('slug_history', function (Blueprint $table) {
            $table->id();
            // constrained() already gives page_id its own index for the FK;
            // no separate index() call needed.
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slug_history');
    }
};
