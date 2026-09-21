<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The evidence layer over a project's location.
 *
 * The place fields already exist (area_id is the district, plus city,
 * neighborhood, landmark). What was missing is provenance and trust:
 * where the location came from, a free note describing it, and a single
 * flag that decides whether it may power SEO. Without `location_verified`
 * a district is stored but never emitted - a location we cannot stand
 * behind is not a local signal, it is a liability.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('location_note')->nullable()->after('landmark');
            $table->string('location_source')->nullable()->after('location_note');
            $table->boolean('location_verified')->default(false)->after('location_source');

            $table->index('location_verified');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['location_verified']);
            $table->dropColumn(['location_note', 'location_source', 'location_verified']);
        });
    }
};
