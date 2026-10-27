<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Link orphan project pages → their primary service page
        $this->linkProjectsToServices();

        // Link orphan article pages → their related service pages
        $this->linkArticlesToServices();

        // Link orphan area pages → their primary active service page
        $this->linkAreasToServices();

        // Link about page from main service page (id not hardcoded — found by type)
        $this->linkAboutPage();
    }

    private function linkProjectsToServices(): void
    {
        // Find published project pages with no inbound internal_links, joined to their
        // primary service page. MIN(sp.id) picks a deterministic anchor page.
        $rows = DB::select("
            SELECT
                MIN(sp.id) AS from_page_id,
                p.id       AS to_page_id,
                p.title    AS anchor_text
            FROM pages p
            LEFT JOIN internal_links il ON p.id = il.to_page_id
            JOIN projects proj
                ON p.pageable_id = proj.id
               AND p.pageable_type LIKE '%Project'
            JOIN project_service ps ON proj.id = ps.project_id
            JOIN pages sp
                ON sp.pageable_id = ps.service_id
               AND sp.pageable_type LIKE '%Service'
               AND sp.status = 'published'
            WHERE p.status = 'published'
              AND p.deleted_at IS NULL
              AND il.id IS NULL
            GROUP BY p.id, p.title
        ");

        $this->insertLinks($rows, 'مشاريع منجزة');
    }

    private function linkArticlesToServices(): void
    {
        $rows = DB::select("
            SELECT
                MIN(sp.id) AS from_page_id,
                p.id       AS to_page_id,
                p.title    AS anchor_text
            FROM pages p
            LEFT JOIN internal_links il ON p.id = il.to_page_id
            JOIN articles art
                ON p.pageable_id = art.id
               AND p.pageable_type LIKE '%Article'
            JOIN article_service ars ON art.id = ars.article_id
            JOIN pages sp
                ON sp.pageable_id = ars.service_id
               AND sp.pageable_type LIKE '%Service'
               AND sp.status = 'published'
            WHERE p.status = 'published'
              AND p.deleted_at IS NULL
              AND il.id IS NULL
            GROUP BY p.id, p.title
        ");

        $this->insertLinks($rows, 'مقالات ذات صلة');
    }

    private function linkAreasToServices(): void
    {
        $rows = DB::select("
            SELECT
                MIN(sp.id) AS from_page_id,
                p.id       AS to_page_id,
                p.title    AS anchor_text
            FROM pages p
            LEFT JOIN internal_links il ON p.id = il.to_page_id
            JOIN areas a
                ON p.pageable_id = a.id
               AND p.pageable_type LIKE '%Area'
            JOIN service_area sa
                ON a.id = sa.area_id
               AND sa.is_active = 1
            JOIN pages sp
                ON sp.pageable_id = sa.service_id
               AND sp.pageable_type LIKE '%Service'
               AND sp.status = 'published'
            WHERE p.status = 'published'
              AND p.deleted_at IS NULL
              AND il.id IS NULL
            GROUP BY p.id, p.title
        ");

        $this->insertLinks($rows, 'مناطق نخدمها');
    }

    private function linkAboutPage(): void
    {
        // If the about page has no inbound link, link it from the first service page.
        $aboutPage = DB::table('pages')
            ->where('type', 'about')
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('internal_links')
                  ->whereColumn('to_page_id', 'pages.id');
            })
            ->first(['id', 'title']);

        if (! $aboutPage) {
            return;
        }

        $firstServicePage = DB::table('pages')
            ->where('type', 'service')
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        if (! $firstServicePage) {
            return;
        }

        DB::table('internal_links')->insert([
            'from_page_id' => $firstServicePage,
            'to_page_id'   => $aboutPage->id,
            'anchor_text'  => $aboutPage->title,
            'context'      => 'روابط ذات صلة',
            'sort_order'   => 0,
            'is_active'    => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /** @param  array<object{from_page_id: int, to_page_id: int, anchor_text: string}>  $rows */
    private function insertLinks(array $rows, string $context): void
    {
        foreach ($rows as $row) {
            DB::table('internal_links')->insertOrIgnore([
                'from_page_id' => $row->from_page_id,
                'to_page_id'   => $row->to_page_id,
                'anchor_text'  => $row->anchor_text,
                'context'      => $context,
                'sort_order'   => 0,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: do not remove links on rollback (content data).
    }
};
