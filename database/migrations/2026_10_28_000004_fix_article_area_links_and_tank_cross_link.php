<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two fixes in one:
 *
 * 1. article_area entries for A21/A22/A23
 *    The build_article_area_internal_links migration only covered articles 1-20.
 *    A21 (عقود), A22 (خزانات-guide), A23 (قصور) have zero area associations.
 *
 * 2. Cross-link A13 ↔ A22 to resolve cannibalization
 *    A13 = WHEN/timing — links to A22 for the HOW
 *    A22 = HOW/steps  — links to A13 for the WHEN
 *    Both tables: internal_links (structured) + content_blocks HTML (visible).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        // ── 1. article_area for A21 / A22 / A23 ─────────────────────────────
        //
        // area_id legend (from existing migration):
        //   1=العليا  2=الملقا  3=النرجس  4=الياسمين  9=القيروان  10=حطين
        //   11=الصحافة  12=الربيع  15=الغدير  19=التعاون  24=العارض
        //   25=النخيل  30=الرمال  40=المونسية  87=المحمدية
        $map = [
            // A21 – عقود التنظيف السنوية: offices (التعاون، العليا، المحمدية) + upscale residential
            21 => [1, 2, 4, 10, 11, 12, 19, 87],

            // A22 – تنظيف خزانات المياه (guide): new-build areas where tank cleaning is top-of-mind
            22 => [3, 4, 9, 12, 15, 24, 30, 40],

            // A23 – تنظيف القصور والعقارات الكبيرة: upscale villas + gated compounds
            23 => [1, 2, 3, 4, 10, 11, 12, 25],
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

        // ── 2. Cross-link A13 ↔ A22 ─────────────────────────────────────────
        //
        // page IDs:  A13 = 181   A22 = 190
        $a13PageId = 181;
        $a22PageId = 190;

        // 2a. internal_links table (structured — used by SEO dashboard)
        DB::table('internal_links')->insertOrIgnore([
            'from_page_id' => $a13PageId,
            'to_page_id' => $a22PageId,
            'anchor_text' => 'دليل تنظيف خزانات المياه — الخطوات والمعايير',
            'context' => 'cross_link',
            'sort_order' => 99,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('internal_links')->insertOrIgnore([
            'from_page_id' => $a22PageId,
            'to_page_id' => $a13PageId,
            'anchor_text' => 'كل كم مرة تنظّف خزان المياه',
            'context' => 'cross_link',
            'sort_order' => 99,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2b. Append cross-link paragraph to A13 rich_text block
        $a13Block = DB::table('content_blocks')
            ->where('page_id', $a13PageId)
            ->where('type', 'rich_text')
            ->first();

        if ($a13Block) {
            $data = json_decode($a13Block->data, true);
            $content = $data['content'] ?? '';

            $crossLink = '<p>هذه المقالة تجيب عن <em>متى</em> تنظّف الخزان. لمعرفة <em>كيف</em> يتم التنظيف الاحترافي خطوة بخطوة والفرق بين التنظيف البسيط والتعقيم الكامل اقرأ: <a href="/blog/water-tank-cleaning-guide-riyadh">دليل تنظيف خزانات المياه — الخطوات والمعايير والضمانات</a>.</p>';

            if (! str_contains($content, 'water-tank-cleaning-guide-riyadh')) {
                $data['content'] = $content."\n\n".$crossLink;

                DB::table('content_blocks')
                    ->where('page_id', $a13PageId)
                    ->where('type', 'rich_text')
                    ->update([
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => $now,
                    ]);
            }
        }

        // 2c. Append cross-link paragraph to A22 rich_text block
        $a22Block = DB::table('content_blocks')
            ->where('page_id', $a22PageId)
            ->where('type', 'rich_text')
            ->first();

        if ($a22Block) {
            $data = json_decode($a22Block->data, true);
            $content = $data['content'] ?? '';

            $crossLink = '<p>هذه المقالة تشرح <em>كيف</em> يُنظَّف الخزان. لمعرفة <em>متى</em> يحتاج خزانك للتنظيف والعلامات التحذيرية التي لا تتجاهلها اقرأ: <a href="/blog/water-tank-cleaning-how-often-riyadh">كل كم مرة تنظّف خزان المياه وكيف تعرف الوقت</a>.</p>';

            if (! str_contains($content, 'water-tank-cleaning-how-often-riyadh')) {
                $data['content'] = $content."\n\n".$crossLink;

                DB::table('content_blocks')
                    ->where('page_id', $a22PageId)
                    ->where('type', 'rich_text')
                    ->update([
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('article_area')->whereIn('article_id', [21, 22, 23])->delete();

        DB::table('internal_links')
            ->whereIn('from_page_id', [181, 190])
            ->whereIn('to_page_id', [181, 190])
            ->where('context', 'cross_link')
            ->delete();
    }
};
