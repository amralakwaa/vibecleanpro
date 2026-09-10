<?php

namespace App\Console\Commands;

use App\Seo\BrokenLinkDetector;
use App\Seo\OrphanPageAnalyzer;
use Illuminate\Console\Command;

/**
 * Thin CLI wrapper around BrokenLinkDetector + OrphanPageAnalyzer - same
 * services the SEO Dashboard widget uses, so the two can never disagree.
 */
class SeoCheckLinksCommand extends Command
{
    protected $signature = 'seo:check-links';

    protected $description = 'Report broken internal links and orphan (unlinked) published pages';

    public function handle(BrokenLinkDetector $brokenLinks, OrphanPageAnalyzer $orphans): int
    {
        $broken = $brokenLinks->detect();

        $this->info('Broken internal links: '.$broken->count());

        foreach ($broken as $item) {
            $from = $item->link->fromPage?->title ?? "#{$item->link->from_page_id}";
            $to = $item->link->toPage?->title ?? "#{$item->link->to_page_id}";
            $this->line("  <fg=red>✕</> {$from} → {$to} ({$item->reason})");
        }

        $this->newLine();

        $orphanPages = $orphans->orphans();
        $this->info('Orphan published pages: '.$orphanPages->count());

        foreach ($orphanPages as $page) {
            $this->line("  <fg=yellow>⚠</> #{$page->id} {$page->title} ({$page->slug})");
        }

        return ($broken->isEmpty() && $orphanPages->isEmpty()) ? self::SUCCESS : self::FAILURE;
    }
}
