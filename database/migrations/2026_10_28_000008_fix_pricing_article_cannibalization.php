<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $articlePage = DB::table('pages')
            ->where('slug', 'cleaning-prices-riyadh')
            ->where('pageable_type', 'article')
            ->first();

        if (! $articlePage) {
            return;
        }

        // Add /pricing link to the "أسعار خدمات محددة" block (position 6, rich_text).
        // This resolves keyword cannibalization: the article becomes the informational
        // guide that explicitly delegates the transactional intent to /pricing.
        $block = DB::table('content_blocks')
            ->where('page_id', $articlePage->id)
            ->where('type', 'rich_text')
            ->where('position', 6)
            ->first();

        if ($block) {
            $data = json_decode($block->data, true);
            $content = $data['content'] ?? '';

            if (! str_contains($content, '/pricing')) {
                $newItem = '<li><a href="/pricing">صفحة أسعار فايب كلين برو التفصيلية</a></li>';
                $content = str_replace('</ul>', $newItem.'</ul>', $content);
                DB::table('content_blocks')
                    ->where('id', $block->id)
                    ->update([
                        'data' => json_encode(['content' => $content], JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            }
        }

        // Register the internal link so the link-graph stays accurate.
        $pricingPage = DB::table('pages')->where('slug', 'pricing')->first();

        if ($pricingPage) {
            $exists = DB::table('internal_links')
                ->where('from_page_id', $articlePage->id)
                ->where('to_page_id', $pricingPage->id)
                ->exists();

            if (! $exists) {
                DB::table('internal_links')->insert([
                    'from_page_id' => $articlePage->id,
                    'to_page_id' => $pricingPage->id,
                    'anchor_text' => 'صفحة أسعار فايب كلين برو التفصيلية',
                    'context' => 'أسعار خدمات محددة',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
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

        // Remove /pricing link from the block.
        $block = DB::table('content_blocks')
            ->where('page_id', $articlePage->id)
            ->where('type', 'rich_text')
            ->where('position', 6)
            ->first();

        if ($block) {
            $data = json_decode($block->data, true);
            $content = $data['content'] ?? '';
            $content = str_replace(
                '<li><a href="/pricing">صفحة أسعار فايب كلين برو التفصيلية</a></li>',
                '',
                $content
            );
            DB::table('content_blocks')
                ->where('id', $block->id)
                ->update([
                    'data' => json_encode(['content' => $content], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }

        $pricingPage = DB::table('pages')->where('slug', 'pricing')->first();
        if ($pricingPage) {
            DB::table('internal_links')
                ->where('from_page_id', $articlePage->id)
                ->where('to_page_id', $pricingPage->id)
                ->where('anchor_text', 'صفحة أسعار فايب كلين برو التفصيلية')
                ->delete();
        }
    }
};
