<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service pricing lives on the service row - one price policy per
 * service, edited by the admin, never typed into a template.
 *
 * Two numeric columns cover every mode (see ServicePricingMode):
 *   quote_only    -> no numbers
 *   starting_from -> price_min
 *   fixed         -> price_min
 *   range         -> price_min + price_max
 *   per_unit      -> price_min + price_unit
 * A separate `price` column for fixed/starting would duplicate price_min
 * and invite the two disagreeing. show_price lets a real price stay on
 * file while the public site says "on request".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('pricing_mode', 20)->default('quote_only')->after('short_description');
            $table->decimal('price_min', 10, 2)->nullable()->after('pricing_mode');
            $table->decimal('price_max', 10, 2)->nullable()->after('price_min');
            $table->string('price_unit', 40)->nullable()->after('price_max');
            $table->string('price_note', 180)->nullable()->after('price_unit');
            $table->boolean('show_price')->default(true)->after('price_note');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['pricing_mode', 'price_min', 'price_max', 'price_unit', 'price_note', 'show_price']);
        });
    }
};
