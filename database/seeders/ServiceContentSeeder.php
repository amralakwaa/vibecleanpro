<?php

namespace Database\Seeders;

use App\Console\Commands\ImportMediaLibrary;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\InternalLink;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Wave service content from database/seeders/content/wave*-services*.php.
 *
 * Two modes:
 *  - page:   fills a service page that has no content yet. Skipped once an
 *            editor has written anything, so nothing is ever overwritten.
 *  - append: adds named sections to an already-written page. Each section
 *            is stamped with its key in the block data, so re-running adds
 *            nothing twice.
 *
 * Publishes nothing: pages go live through site:launch and the Publishing
 * Gate. An image block is written only when its photo exists and is
 * approved, so a missing photo never blocks a page.
 */
class ServiceContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = [];

        // Several files may carry definitions for the same slug (a page in
        // one batch, extra sections in a later one), so they are collected
        // per slug and applied in file order rather than overwriting.
        foreach (glob(database_path('seeders/content/wave*-services*.php')) as $file) {
            foreach (require $file as $slug => $definition) {
                $content[$slug][] = $definition;
            }
        }

        foreach ($content as $slug => $definitions) {
            $page = Page::query()->where('slug', $slug)->where('type', PageType::Service)->with('pageable')->first();

            if (! $page) {
                $this->command?->warn("Service page {$slug} not found - skipped.");

                continue;
            }

            foreach ($definitions as $definition) {
                $definition['mode'] === 'append'
                    ? $this->appendSections($page->refresh(), $definition['sections'])
                    : $this->fillPage($page, $definition);
            }

            $this->syncBodyLinks($page->refresh());
        }
    }

    /**
     * Records the service links that the page body actually contains, so the
     * internal-link table describes the rendered HTML instead of a plan. A
     * row is never written for an anchor that is not in the text.
     */
    private function syncBodyLinks(Page $page): void
    {
        $html = $page->contentBlocks
            ->map(fn (ContentBlock $block) => is_array($block->data) ? ($block->data['content'] ?? '') : '')
            ->implode(' ');

        preg_match_all('#href="/services/([a-z0-9-]+)"#', $html, $matches);

        foreach (array_unique($matches[1]) as $position => $slug) {
            $target = Page::query()->where('slug', $slug)->where('type', PageType::Service)->first();

            if (! $target || $target->is($page)) {
                continue;
            }

            InternalLink::query()->firstOrCreate(
                ['from_page_id' => $page->id, 'to_page_id' => $target->id],
                ['anchor_text' => $target->title, 'context' => 'body_link', 'sort_order' => $position + 1, 'is_active' => true],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function fillPage(Page $page, array $definition): void
    {
        if ($page->contentBlocks()->exists()) {
            $this->command?->line("  {$page->slug}: already has content - left untouched.");

            return;
        }

        $page->update(['title' => $definition['page_title']]);
        $page->seoMetadata()->updateOrCreate([], [
            'meta_title' => $definition['meta_title'],
            'meta_description' => $definition['meta_description'],
            'robots_index' => true,
            'robots_follow' => true,
        ]);

        if ($page->pageable instanceof Service) {
            $page->pageable->update([
                'short_description' => $definition['short_description'],
                'featured_media_id' => $this->mediaId($definition['featured_media_file'] ?? null) ?? $page->pageable->featured_media_id,
            ]);
        }

        foreach ($definition['blocks'] as $position => $block) {
            $this->writeBlock($page, $block, $position + 1);
        }

        foreach ($definition['faqs'] ?? [] as $position => [$question, $answer]) {
            $page->faqs()->create(['question' => $question, 'answer' => $answer, 'sort_order' => $position + 1, 'is_active' => true]);
        }

        $this->command?->info("  {$page->slug}: content written.");
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     */
    private function appendSections(Page $page, array $sections): void
    {
        foreach ($sections as $section) {
            $exists = $page->contentBlocks()->get()
                ->contains(fn (ContentBlock $block) => ($block->data['section'] ?? null) === $section['section']);

            if ($exists) {
                $this->command?->line("  {$page->slug}: section {$section['section']} already present.");

                continue;
            }

            $position = (int) $page->contentBlocks()->max('position');

            foreach ($section['blocks'] as $block) {
                $this->writeBlock($page, $block, ++$position, $section['section']);
            }

            $this->command?->info("  {$page->slug}: section {$section['section']} added.");
        }
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function writeBlock(Page $page, array $block, int $position, ?string $section = null): void
    {
        $data = match ($block['type']) {
            'rich_text' => ['content' => implode("\n", $block['html'])],
            'image' => ['media_id' => (string) $this->mediaId($block['media_file']), 'caption' => $block['caption'] ?? null],
            'steps' => ['heading' => $block['heading'], 'items' => $block['items']],
            'features' => ['heading' => $block['heading'], 'items' => $block['items']],
            'cta' => ['heading' => $block['heading'], 'button_label' => $block['label'], 'button_url' => url($block['url'])],
            'faq' => [],
            default => [],
        };

        // A photo that is missing or not approved yet simply means no image
        // block - never a blocked page, and never a placeholder pretending
        // to be our own work.
        if ($block['type'] === 'image' && blank($data['media_id'])) {
            return;
        }

        if ($section !== null) {
            $data['section'] = $section;
        }

        $page->contentBlocks()->create(['type' => $block['type'], 'data' => $data, 'position' => $position, 'is_active' => true]);
    }

    private function mediaId(?string $file): ?int
    {
        if (blank($file)) {
            return null;
        }

        return Media::query()
            ->where('path', ImportMediaLibrary::DIRECTORY.'/'.$file)
            ->where('status', 'ready')
            ->value('id');
    }
}
