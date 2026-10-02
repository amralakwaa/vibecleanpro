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
        Schema::table('business_profiles', function (Blueprint $table) {
            // Who designed and built the site, shown as a footer credit.
            // Both nullable: the credit renders only when a name is set, so a
            // profile without them emits no phone link and no extra name.
            $table->string('credit_name')->nullable()->after('name_ar');
            $table->string('credit_phone')->nullable()->after('credit_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn(['credit_name', 'credit_phone']);
        });
    }
};
