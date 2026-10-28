<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Adds the "أسعار خدمات محددة" rich_text block (position 6) to the
    // cleaning-prices-riyadh article on environments where it doesn't exist
    // (production). This block lists related pricing articles and includes a
    // direct link to /pricing, resolving keyword cannibalization between the
    // informational article and the transactional pricing page.
    public function up(): void
    {
        $articlePage = DB::table('pages')
            ->where('slug', 'cleaning-prices-riyadh')
            ->where('pageable_type', 'article')
            ->first();

        if (! $articlePage) {
            return;
        }

        $exists = DB::table('content_blocks')
            ->where('page_id', $articlePage->id)
            ->where('type', 'rich_text')
            ->where('position', 6)
            ->exists();

        if ($exists) {
            return;
        }

        $content = '<h2>أسعار خدمات محددة</h2>'
            .'<ul>'
            .'<li><a href="/blog/villa-cleaning-cost-riyadh">كم تكلفة تنظيف الفيلا بالرياض؟</a></li>'
            .'<li><a href="/blog/facade-cleaning-cost-riyadh">ما الذي يحدد سعر تنظيف الواجهات؟</a></li>'
            .'<li><a href="/blog/sofa-majlis-cleaning-cost-riyadh">كم تكلفة تنظيف الكنب والمجالس؟</a></li>'
            .'<li><a href="/pricing">صفحة أسعار فايب كلين برو التفصيلية</a></li>'
            .'</ul>';

        DB::table('content_blocks')->insert([
            'page_id' => $articlePage->id,
            'type' => 'rich_text',
            'data' => json_encode(['content' => $content], JSON_UNESCAPED_UNICODE),
            'position' => 6,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $articlePage = DB::table('pages')
            ->where('slug', 'cleaning-prices-riyadh')
            ->where('pageable_type', 'article')
            ->first();

        if (! $articlePage) {
            return;
        }

        DB::table('content_blocks')
            ->where('page_id', $articlePage->id)
            ->where('type', 'rich_text')
            ->where('position', 6)
            ->where('data', 'like', '%/pricing%')
            ->delete();
    }
};
