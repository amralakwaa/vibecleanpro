<?php

namespace App\Seo\Actions;

use App\Enums\RedirectSource;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SlugHistory;
use App\Seo\UrlResolver;
use Illuminate\Support\Facades\DB;

/**
 * Runs whenever a Page's slug changes on an already-persisted record
 * (wired from PageObserver::updating()). Protects the old URL permanently:
 *
 * 1. Records the old slug in slug_history (append-only, ignored if already
 *    recorded - a page can be renamed back and forth without error).
 * 2. Flattens any existing chain: if some redirect already points *to* the
 *    old path (A -> old), it is repointed straight to the new path
 *    (A -> new) instead of leaving a two-hop chain behind.
 * 3. Upserts old-path -> new-path as a single redirect (never duplicated,
 *    since from_path is unique; never self-referencing, since we skip
 *    entirely when the path didn't actually change).
 * 4. If the new path was itself the source of some other redirect (an
 *    editor had previously redirected this exact path elsewhere), that
 *    redirect is removed - the path is live content now, not a redirect
 *    source.
 *
 * We protect the old URL on every slug change to an existing record,
 * regardless of the page's status *at the moment of the change*: the old
 * slug may well have been published and indexed at some earlier point, and
 * an extra redirect row is harmless, while a missed one is a real broken
 * link.
 */
class RecordSlugChange
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function handle(Page $page, string $oldSlug, string $newSlug): void
    {
        if ($oldSlug === $newSlug) {
            return;
        }

        $oldPath = $this->urlResolver->pathFor($page->type, $oldSlug);
        $newPath = $this->urlResolver->pathFor($page->type, $newSlug);

        if ($oldPath === $newPath) {
            return;
        }

        DB::transaction(function () use ($page, $oldSlug, $oldPath, $newPath) {
            SlugHistory::query()->firstOrCreate([
                'page_id' => $page->id,
                'slug' => $oldSlug,
            ]);

            // Flatten: anything currently pointing at the old path should
            // point directly at the new one.
            Redirect::query()
                ->where('to_path', $oldPath)
                ->update(['to_path' => $newPath]);

            // The new path can no longer also be a redirect source.
            Redirect::query()
                ->where('from_path', $newPath)
                ->delete();

            Redirect::query()->updateOrCreate(
                ['from_path' => $oldPath],
                [
                    'to_path' => $newPath,
                    'type' => 301,
                    'source' => RedirectSource::SlugChange,
                    'is_active' => true,
                ],
            );
        });
    }
}
