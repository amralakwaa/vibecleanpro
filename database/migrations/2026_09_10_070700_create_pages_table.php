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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('pageable_type')->nullable();
            $table->unsignedBigInteger('pageable_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            // One Page per entity: this single unique index also serves every
            // pageable_type/pageable_id lookup (morphTo/morphOne resolution),
            // so no separate plain index is added on these two columns.
            $table->unique(['pageable_type', 'pageable_id']);
            $table->index('type');
            $table->index('status');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
