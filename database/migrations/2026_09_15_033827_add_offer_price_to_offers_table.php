<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An optional numeric price for an offer, entered by the admin. The site
 * never derives it from discount_label (a free-text value statement) and
 * never computes a "before" price: a "بدلًا من" line appears only when
 * the offer covers exactly one service whose own public price is a real
 * number higher than this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->decimal('offer_price', 10, 2)->nullable()->after('discount_label');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('offer_price');
        });
    }
};
