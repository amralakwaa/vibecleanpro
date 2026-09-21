<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaContentSeeder extends Seeder
{
    private const BRAND = ' | فايب كلين برو';

    private const VERSION = 'wave2.0';

    private const AUTHOR = 'area-content-seeder';

    public function run(): void
    {
        $areas = array_merge(
            require database_path('seeders/content/areas-wave1.php'),
            require database_path('seeders/content/areas-north.php'),
            require database_path('seeders/content/areas-east.php'),
            require database_path('seeders/content/areas-west.php'),
            require database_path('seeders/content/areas-central.php'),
            require database_path('seeders/content/areas-south.php'),
        );

        foreach ($areas as $slug => $data) {
            $area = Area::where('slug', $slug)->first();

            if (! $area || ! $area->page) {
                continue;
            }

            $page = $area->page;

            // SEO metadata: always safe to updateOrCreate — these are
            // seeder-managed fields. An editor who later changes them in
            // the panel overrides the seeder value; re-running restores it.
            $page->seoMetadata()->updateOrCreate([], [
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'robots_index' => true,
            ]);

            // Idempotency: replace blocks only when every existing block
            // was created by this seeder (_author marker) or by the older
            // ProductionContentSeeder (no marker). If an editor added or
            // replaced a block, at least one block lacks our markers and
            // we skip the content — their work is protected.
            $blocks = $page->contentBlocks()->get();

            $seederOwned = $blocks->every(fn ($block) => ($block->data['_author'] ?? null) === self::AUTHOR
                || ($block->data['_version'] ?? null) === self::VERSION
                || $this->isGenericTemplate($block),
            );

            if (! $blocks->isEmpty() && ! $seederOwned) {
                continue;
            }

            $page->contentBlocks()->delete();

            $blockTemplate = [
                ['type' => 'rich_text', 'key' => 'intro', 'content' => $data['intro']],
                ['type' => 'rich_text', 'key' => 'context', 'content' => $data['context']],
                ['type' => 'rich_text', 'key' => 'services', 'content' => $data['services_text']],
                ['type' => 'rich_text', 'key' => 'process', 'content' => $data['process']],
                ['type' => 'rich_text', 'key' => 'trust', 'content' => $data['trust']],
            ];

            foreach ($blockTemplate as $index => $block) {
                $page->contentBlocks()->create([
                    'type' => $block['type'],
                    'position' => $index + 1,
                    'is_active' => true,
                    'data' => [
                        'content' => $block['content'],
                        '_key' => $block['key'],
                        '_version' => self::VERSION,
                        '_author' => self::AUTHOR,
                    ],
                ]);
            }

            // FAQ position marker — activates the FAQ section in the template.
            $page->contentBlocks()->create([
                'type' => 'faq',
                'position' => count($blockTemplate) + 1,
                'is_active' => true,
                'data' => [
                    'heading' => 'أسئلة شائعة',
                    '_key' => 'faq',
                    '_version' => self::VERSION,
                    '_author' => self::AUTHOR,
                ],
            ]);

            $page->contentBlocks()->create([
                'type' => 'cta',
                'position' => count($blockTemplate) + 2,
                'is_active' => true,
                'data' => [
                    'heading' => $data['cta_heading'],
                    'button_label' => 'اطلب عرض سعر مكتوب',
                    'button_url' => route('public.quote', ['area' => $area->id]),
                    '_key' => 'cta',
                    '_version' => self::VERSION,
                    '_author' => self::AUTHOR,
                ],
            ]);

            // FAQs: replace only when blocks were also replaced.
            $page->faqs()->delete();

            foreach ($data['faqs'] as $index => $faqPair) {
                $page->faqs()->create([
                    'question' => $faqPair[0],
                    'answer' => $faqPair[1],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Detect the generic 2-block pattern left by ProductionContentSeeder
     * so the first run of AreaContentSeeder can replace it.
     */
    private function isGenericTemplate(object $block): bool
    {
        $data = $block->data;

        // Rich-text block with the generic intro from ProductionContentSeeder
        $content = $data['content'] ?? '';
        if (is_string($content) && str_contains($content, 'تغطي فايب كلين برو')) {
            return true;
        }

        // CTA block from ProductionContentSeeder — has no seeder-ownership markers
        if ($block->type === 'cta' && ! isset($data['_author']) && ! isset($data['_version'])) {
            return true;
        }

        return false;
    }
}
