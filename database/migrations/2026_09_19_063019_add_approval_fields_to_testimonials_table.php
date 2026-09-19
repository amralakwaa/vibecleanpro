<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A review is only shown publicly after someone holding
     * approve_testimonial confirms where it came from and that the customer
     * agreed to it being quoted. Existing rows stay unapproved.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('source', 30)->nullable()->after('content');
            $table->string('source_ref')->nullable()->after('source');
            $table->boolean('consent_confirmed')->default(false)->after('source_ref');
            $table->timestamp('approved_at')->nullable()->after('consent_confirmed');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->index('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['approved_at']);
            $table->dropColumn(['source', 'source_ref', 'consent_confirmed', 'approved_at', 'approved_by']);
        });
    }
};
