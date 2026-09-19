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
        Schema::create('conversion_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 40);
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('page_path', 255);
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('source', 30);
            $table->string('utm_campaign')->nullable();
            $table->string('device_type', 10);
            $table->char('session_hash', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'created_at']);
            $table->index(['session_hash', 'event_type', 'page_path', 'created_at'], 'conversion_events_dedupe_index');
            $table->index(['source', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_events');
    }
};
