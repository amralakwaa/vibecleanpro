<?php

namespace App\Console\Commands;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Seo\PublishingGate;
use Illuminate\Console\Command;

/**
 * Thin CLI wrapper around PublishingGate - no logic lives here that isn't
 * already in the Seo services, so this and the SEO Dashboard widget can
 * never disagree.
 */
class SeoAuditCommand extends Command
{
    protected $signature = 'seo:audit';

    protected $description = 'Run the Publishing Gate against every published page and report errors/warnings';

    public function handle(PublishingGate $gate): int
    {
        $pages = Page::query()
            ->where('status', PageStatus::Published)
            ->with(['contentBlocks', 'seoMetadata', 'pageable'])
            ->get();

        $errorCount = 0;
        $warningCount = 0;

        foreach ($pages as $page) {
            $result = $gate->evaluate($page);

            if ($result->canPublish() && $result->warnings()->isEmpty()) {
                continue;
            }

            $this->line("<comment>#{$page->id}</comment> {$page->title} ({$page->slug})");

            foreach ($result->errors() as $error) {
                $this->line("  <fg=red>✕ {$error->message}</>");
                $errorCount++;
            }

            foreach ($result->warnings() as $warning) {
                $this->line("  <fg=yellow>⚠ {$warning->message}</>");
                $warningCount++;
            }
        }

        $this->newLine();
        $this->info("Checked {$pages->count()} published pages: {$errorCount} error(s), {$warningCount} warning(s).");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
