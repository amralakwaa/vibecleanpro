<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Builds the article-area internal-link graph.
 *
 * Area IDs (15 published areas):
 *   1=العليا  2=الملقا  3=النرجس  4=الياسمين  9=القيروان  10=حطين
 *   11=الصحافة  12=الربيع  15=الغدير  19=التعاون  24=العارض
 *   25=النخيل  30=الرمال  40=المونسية  87=المحمدية
 *
 * Each article is mapped to the most topically relevant areas.
 * General (pricing/guides/tips) articles get a broad residential mix.
 * Specialist articles target neighborhoods where that service demand is highest.
 * Idempotent: insertOrIgnore skips existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        // article_id → [area_ids]
        $map = [
            // A1 – cleaning prices: broadly relevant, mix all area types
            1 => [1, 10, 3, 19, 11, 15, 30, 12],
            // A2 – how to choose a company: broadly relevant
            2 => [1, 2, 4, 9, 19, 11, 15, 40],
            // A3 – post-construction checklist: new-build neighborhoods
            3 => [3, 4, 9, 24, 30, 12],
            // A4 – villa cleaning cost: villa-dense neighborhoods
            4 => [10, 2, 25, 12, 24, 15],
            // A5 – villa cleaning checklist: same villa-dense focus
            5 => [10, 2, 25, 12, 3, 4],
            // A6 – facade cleaning cost: commercial/mixed-use areas
            6 => [1, 19, 11, 87],
            // A7 – facade access methods: same commercial focus
            7 => [1, 19, 11, 25],
            // A8 – streak-free glass: broad residential + commercial
            8 => [1, 10, 2, 19, 11, 40],
            // A9 – office cleaning scope: commercial districts
            9 => [1, 19, 11, 87, 40],
            // A10 – courtyard/interlock cleaning: villa + house neighborhoods
            10 => [10, 2, 25, 12, 24, 3],
            // A11 – sofa/majlis steam vs wash: broad residential
            11 => [10, 2, 1, 19, 15, 30],
            // A12 – AC cleaning: universal need, 8 varied areas
            12 => [1, 3, 4, 9, 10, 11, 15, 19],
            // A13 – water tank cleaning: broad, eastern + western areas
            13 => [2, 12, 24, 25, 30, 40, 87, 1],
            // A14 – sofa/majlis cost: broad residential
            14 => [10, 1, 2, 19, 11, 15, 30, 40],
            // A15 – pest control: newer builds + eastern areas
            15 => [3, 4, 9, 12, 24, 30, 40, 87],
            // A16 – apartment move in/out: apartment-dense neighborhoods
            16 => [19, 11, 15, 30, 40, 87, 1, 4],
            // A17 – marble polishing: prestige / marble-heavy neighborhoods
            17 => [10, 2, 25, 1, 19, 3],
            // A18 – disinfection: broad reach
            18 => [1, 3, 9, 11, 15, 19, 30, 40],
            // A19 – hourly cleaning: broad residential
            19 => [10, 2, 3, 4, 11, 15, 19, 30],
            // A20 – specialized team vs domestic workers: broad reach
            20 => [1, 2, 3, 9, 10, 19, 24, 25],
        ];

        foreach ($map as $articleId => $areaIds) {
            foreach ($areaIds as $areaId) {
                DB::table('article_area')->insertOrIgnore([
                    'article_id' => $articleId,
                    'area_id' => $areaId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('article_area')
            ->whereIn('article_id', range(1, 20))
            ->delete();
    }
};
