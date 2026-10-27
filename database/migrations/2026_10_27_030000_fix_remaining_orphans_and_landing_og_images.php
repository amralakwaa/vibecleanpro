<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix orphan pages that exist only on production (not in local seeder):
     * projects with no project_service rows, services 186/187 (مساجد/ثريات),
     * offer 188, and landing pages missing OG images.
     *
     * Also adds a fallback OG image for any remaining page without one.
     */
    public function up(): void
    {
        // Projects with no project_service association — link from closest service page.
        // Rather than hardcoding page IDs, we resolve by slug so this is portable.
        $this->linkProjectsWithoutServices();

        // Services 186 (مساجد) and 187 (ثريات) exist only on production; they have
        // no service_area rows, so the previous migration left them orphaned.
        // Link مساجد from مكاتب (page type=service, slug=office-cleaning).
        // Link ثريات from فلل (slug=villa-cleaning).
        $this->linkServicesBySlug('mosque-cleaning', 'office-cleaning', 'تنظيف المساجد بالرياض', 'خدمات ذات صلة');
        $this->linkServicesBySlug('chandelier-cleaning', 'villa-cleaning', 'تنظيف الثريات والنجف بالرياض', 'خدمات ذات صلة');

        // Offer 188 — link from the main services page (منازل).
        $this->linkOfferFromService();

        // Fallback OG image for any remaining page (landing pages or future additions).
        $this->fillRemainingOgImages();
    }

    private function linkProjectsWithoutServices(): void
    {
        // Find published project pages whose project has no project_service row.
        $rows = DB::table('pages as p')
            ->join('projects as proj', function ($j) {
                $j->on('p.pageable_id', '=', 'proj.id')
                  ->where('p.pageable_type', 'like', '%Project');
            })
            ->leftJoin('project_service as ps', 'proj.id', '=', 'ps.project_id')
            ->leftJoin('internal_links as il', 'p.id', '=', 'il.to_page_id')
            ->where('p.status', 'published')
            ->whereNull('p.deleted_at')
            ->whereNull('il.id')
            ->whereNull('ps.project_id')
            ->select(['p.id as to_page_id', 'p.title as anchor_text'])
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        // Link from واجهات (facade/exterior cleaning) as the most generic fallback.
        $fromPage = DB::table('pages')
            ->where('type', 'service')
            ->where('slug', 'facade-cleaning')
            ->where('status', 'published')
            ->value('id');

        if (! $fromPage) {
            // Final fallback: first published service page.
            $fromPage = DB::table('pages')
                ->where('type', 'service')
                ->where('status', 'published')
                ->orderBy('id')
                ->value('id');
        }

        if (! $fromPage) {
            return;
        }

        foreach ($rows as $row) {
            DB::table('internal_links')->insertOrIgnore([
                'from_page_id' => $fromPage,
                'to_page_id'   => $row->to_page_id,
                'anchor_text'  => $row->anchor_text,
                'context'      => 'مشاريع منجزة',
                'sort_order'   => 0,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    private function linkServicesBySlug(string $targetSlug, string $sourceSlug, string $anchorText, string $context): void
    {
        $targetPage = DB::table('pages')
            ->where('type', 'service')
            ->where('slug', $targetSlug)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('internal_links')
                  ->whereColumn('to_page_id', 'pages.id');
            })
            ->value('id');

        if (! $targetPage) {
            return;
        }

        $sourcePage = DB::table('pages')
            ->where('type', 'service')
            ->where('slug', $sourceSlug)
            ->where('status', 'published')
            ->value('id');

        if (! $sourcePage) {
            return;
        }

        DB::table('internal_links')->insertOrIgnore([
            'from_page_id' => $sourcePage,
            'to_page_id'   => $targetPage,
            'anchor_text'  => $anchorText,
            'context'      => $context,
            'sort_order'   => 0,
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function linkOfferFromService(): void
    {
        $orphanOffers = DB::table('pages as p')
            ->leftJoin('internal_links as il', 'p.id', '=', 'il.to_page_id')
            ->where('p.type', 'offer')
            ->where('p.status', 'published')
            ->whereNull('p.deleted_at')
            ->whereNull('il.id')
            ->select(['p.id as to_page_id', 'p.title as anchor_text'])
            ->get();

        if ($orphanOffers->isEmpty()) {
            return;
        }

        $sourcePage = DB::table('pages')
            ->where('type', 'service')
            ->where('status', 'published')
            ->orderBy('id')
            ->value('id');

        if (! $sourcePage) {
            return;
        }

        foreach ($orphanOffers as $offer) {
            DB::table('internal_links')->insertOrIgnore([
                'from_page_id' => $sourcePage,
                'to_page_id'   => $offer->to_page_id,
                'anchor_text'  => $offer->anchor_text,
                'context'      => 'عروض حالية',
                'sort_order'   => 0,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    private function fillRemainingOgImages(): void
    {
        $fallback = DB::table('services')
            ->whereNotNull('featured_media_id')
            ->orderBy('id')
            ->value('featured_media_id');

        if (! $fallback) {
            return;
        }

        // Update any published page whose seo_metadata still has no og_image_media_id.
        DB::table('seo_metadata as sm')
            ->join('pages as p', 'sm.page_id', '=', 'p.id')
            ->where('p.status', 'published')
            ->whereNull('p.deleted_at')
            ->whereNull('sm.og_image_media_id')
            ->update(['sm.og_image_media_id' => $fallback, 'sm.updated_at' => now()]);
    }

    public function down(): void
    {
        // Non-destructive: do not remove links or OG images on rollback.
    }
};
