<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Activates "starting from" public prices on all 19 services.
 * Service 17 (cleaning contracts / B2B) stays quote_only — no public price.
 * All prices are market-rate Riyadh starting points; each carries a note
 * clarifying the variable (area, unit count, etc.) so no price looks fixed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        $prices = [
            1 => [250, 'بحسب المساحة والحالة'],
            2 => [450, 'بحسب الأدوار والملاحق'],
            3 => [200, 'بحسب المساحة'],
            4 => [200, 'للطقم الواحد'],
            5 => [150, 'بحسب الحجم'],
            6 => [120, 'للغرفة الواحدة'],
            7 => [350, 'بحسب المساحة'],
            8 => [300, 'بحسب المساحة'],
            9 => [600, 'بحسب الارتفاع والمساحة'],
            10 => [700, 'بحسب المساحة والحالة'],
            11 => [200, 'للخزان الواحد'],
            12 => [150, 'للوحدة الواحدة'],
            13 => [350, 'بحسب المساحة'],
            14 => [400, 'بحسب الحجم'],
            15 => [350, 'بحسب المساحة'],
            16 => [250, 'للوحدة السكنية'],
            // 17 = cleaning contracts: B2B, stays quote_only, no show_price
            18 => [200, 'بحسب المساحة'],
            19 => [180, 'بحسب العدد والارتفاع'],
        ];

        foreach ($prices as $id => [$min, $note]) {
            DB::table('services')->where('id', $id)->update([
                'pricing_mode' => 'starting_from',
                'price_min' => $min,
                'price_note' => $note,
                'show_price' => true,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('services')
            ->whereIn('id', array_keys([
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
                11, 12, 13, 14, 15, 16, 18, 19,
            ]))
            ->update([
                'pricing_mode' => 'quote_only',
                'price_min' => null,
                'price_note' => null,
                'show_price' => false,
            ]);
    }
};
