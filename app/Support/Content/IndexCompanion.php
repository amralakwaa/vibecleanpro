<?php

namespace App\Support\Content;

use App\Enums\PageType;
use App\Models\Page;
use App\Seo\StructuredDataGenerator;

/**
 * Editor-managed content for the controller-rendered index pages
 * (/contact, /offers, /areas).
 *
 * The companion is an ordinary published Landing page whose slug equals the
 * index path. UrlResolver already resolves it to that same URL, and the
 * standalone catch-all route refuses those reserved slugs, so the page never
 * gets a second address: it only contributes its blocks, FAQs and SEO
 * metadata to the index. No companion published means the index renders
 * exactly as before.
 */
class IndexCompanion
{
    public function __construct(private readonly StructuredDataGenerator $structuredData) {}

    public function find(string $slug): ?Page
    {
        return Page::query()
            ->published()
            ->where('type', PageType::Landing)
            ->whereNull('pageable_type')
            ->where('slug', $slug)
            ->with([
                'seoMetadata',
                'contentBlocks',
                'faqs' => fn ($query) => $query->where('is_active', true),
            ])
            ->first();
    }

    public function title(?Page $companion, string $default): string
    {
        return $companion?->seoMetadata?->meta_title ?: $default;
    }

    public function description(?Page $companion, string $default): string
    {
        return $companion?->seoMetadata?->meta_description ?: $default;
    }

    /**
     * FAQPage markup for exactly the questions the index renders.
     *
     * @return array<int, array<string, mixed>>
     */
    public function structuredData(?Page $companion): array
    {
        return $companion && $companion->faqs->isNotEmpty()
            ? [$this->structuredData->faqPage($companion->faqs)]
            : [];
    }
}
