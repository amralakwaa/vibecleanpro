<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Services
        DB::statement("
            UPDATE seo_metadata sm
            JOIN pages p ON sm.page_id = p.id
            JOIN services s ON p.pageable_id = s.id AND p.pageable_type LIKE '%Service'
            SET sm.og_image_media_id = s.featured_media_id, sm.updated_at = NOW()
            WHERE sm.og_image_media_id IS NULL AND s.featured_media_id IS NOT NULL
        ");

        // Articles
        DB::statement("
            UPDATE seo_metadata sm
            JOIN pages p ON sm.page_id = p.id
            JOIN articles a ON p.pageable_id = a.id AND p.pageable_type LIKE '%Article'
            SET sm.og_image_media_id = a.featured_media_id, sm.updated_at = NOW()
            WHERE sm.og_image_media_id IS NULL AND a.featured_media_id IS NOT NULL
        ");

        // Offers
        DB::statement("
            UPDATE seo_metadata sm
            JOIN pages p ON sm.page_id = p.id
            JOIN offers o ON p.pageable_id = o.id AND p.pageable_type LIKE '%Offer'
            SET sm.og_image_media_id = o.featured_media_id, sm.updated_at = NOW()
            WHERE sm.og_image_media_id IS NULL AND o.featured_media_id IS NOT NULL
        ");

        // Projects: first publishable media (ready/replace) sorted by sort_order then id
        DB::statement("
            UPDATE seo_metadata sm
            JOIN pages p ON sm.page_id = p.id
            JOIN (
                SELECT pm.project_id, MIN(pm.media_id) AS first_media_id
                FROM project_media pm
                JOIN media m ON pm.media_id = m.id
                WHERE m.status IN ('ready', 'replace')
                GROUP BY pm.project_id
            ) best ON best.project_id = p.pageable_id
            SET sm.og_image_media_id = best.first_media_id, sm.updated_at = NOW()
            WHERE p.pageable_type LIKE '%Project' AND sm.og_image_media_id IS NULL
        ");

        // Areas: first active service's featured_media_id
        DB::statement("
            UPDATE seo_metadata sm
            JOIN pages p ON sm.page_id = p.id
            JOIN (
                SELECT sa.area_id, MIN(s.featured_media_id) AS service_media_id
                FROM service_area sa
                JOIN services s ON sa.service_id = s.id
                WHERE sa.is_active = 1 AND s.featured_media_id IS NOT NULL
                GROUP BY sa.area_id
            ) best ON best.area_id = p.pageable_id
            SET sm.og_image_media_id = best.service_media_id, sm.updated_at = NOW()
            WHERE p.pageable_type LIKE '%Area' AND sm.og_image_media_id IS NULL
        ");

        // Static pages (about, legal, trust): fallback to first service's featured media
        DB::statement("
            UPDATE seo_metadata sm
            JOIN pages p ON sm.page_id = p.id
            SET sm.og_image_media_id = (
                SELECT featured_media_id FROM services
                WHERE featured_media_id IS NOT NULL ORDER BY id LIMIT 1
            ), sm.updated_at = NOW()
            WHERE p.type IN ('about', 'legal', 'trust') AND sm.og_image_media_id IS NULL
        ");
    }

    public function down(): void
    {
        // Non-destructive: do not remove OG images on rollback.
    }
};
