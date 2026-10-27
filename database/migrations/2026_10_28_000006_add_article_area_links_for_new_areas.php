<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds article_area entries for the 5 new area pages added in migration 000005.
 * Each new area mirrors the article set of its nearest existing area by type/group.
 *
 *   الملز     (88) ← mirrors العليا     (1)  وسط / commercial-mixed
 *   قرطبة     (89) ← mirrors الرمال     (30) شرق / family residential
 *   الروضة    (90) ← mirrors الملقا     (2)  شمال / upscale villas
 *   العزيزية  (91) ← mirrors النرجس     (3)  new-build / post-construction
 *   الورود    (92) ← mirrors الياسمين   (4)  شمال / quiet family
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        $map = [
            88 => [1, 2, 6, 7, 8, 9, 11, 12, 13, 14, 16, 17, 18, 20, 21, 23],
            89 => [1, 3, 11, 13, 14, 15, 16, 18, 19, 22],
            90 => [2, 4, 5, 8, 10, 11, 13, 14, 17, 19, 20, 21, 23],
            91 => [1, 3, 5, 10, 12, 15, 17, 18, 19, 20, 22, 23],
            92 => [2, 3, 5, 12, 15, 16, 19, 21, 22, 23],
        ];

        foreach ($map as $areaId => $articleIds) {
            foreach ($articleIds as $articleId) {
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
        DB::table('article_area')->whereIn('area_id', [88, 89, 90, 91, 92])->delete();
    }
};
