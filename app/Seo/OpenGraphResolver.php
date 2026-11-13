<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\BusinessProfile;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Fallback chain, in order: explicit OG field -> SEO metadata (meta title/
 * description) -> page/business defaults (page title, business logo).
 * Image URLs are always absolute, so they stay usable once this runs in
 * production behind a real domain.
 */
class OpenGraphResolver
{
    public function __construct(
        private readonly CanonicalResolver $canonical,
        private readonly DefaultShareImage $defaultShareImage,
    ) {}

    /**
     * @return array{title: string, description: ?string, image: ?string, url: string, type: string}
     */
    public function resolve(Page $page): array
    {
        $seo = $page->seoMetadata;

        $image = $this->shareMedia($seo?->ogImage, $this->pageMedia($page))?->url();

        $image ??= $this->defaultShareImage->url();

        if (! $image) {
            $image = BusinessProfile::query()->first()?->logo?->url();
        }

        return [
            'title' => $seo?->og_title ?: ($seo?->meta_title ?: $page->title),
            'description' => $seo?->og_description ?: $seo?->meta_description,
            'image' => $image,
            'url' => $this->canonical->resolve($page),
            'type' => $page->type === PageType::Article ? 'article' : 'website',
        ];
    }

    /**
     * The editor's OG image wins, except that a stock library photo never
     * stands in for real work: when the page has a documented photo of its
     * own, that photo is shared instead. Stock is still used when it is all
     * the page has.
     */
    private function shareMedia(?Media $override, ?Media $own): ?Media
    {
        if ($override && ! $override->isLibraryStock()) {
            return $override;
        }

        if ($own && ! $own->isLibraryStock()) {
            return $own;
        }

        return $override ?? $own;
    }

    /**
     * The photograph the page itself is about: a project's "after" photo
     * (its first photo if it has no after stage), an area's or side of
     * Riyadh's best documented project photo, or a service, article or
     * offer's featured image.
     */
    private function pageMedia(Page $page): ?Media
    {
        $pageable = $page->pageable;

        if ($pageable instanceof Project) {
            return $pageable->media()->wherePivot('stage', 'after')->first() ?? $pageable->media()->first();
        }

        if ($pageable instanceof Area) {
            return $this->documentedPhoto($pageable->projects())
                ?? $this->documentedPhoto($pageable->supportingProjects()->wherePivot('is_active', true));
        }

        if ($pageable instanceof AreaGroup) {
            return $this->documentedPhoto(Project::query()->whereIn('area_id', $pageable->areas()->select('id')));
        }

        if ($pageable && method_exists($pageable, 'featuredMedia')) {
            return $pageable->featuredMedia;
        }

        return null;
    }

    /**
     * The most prominent published project in scope, shared by its "after"
     * photo, or its first photo when no stage is tagged - the same photo the
     * page shows on that project's card.
     *
     * @param  Builder<Project>|Relation<Project, *, *>  $projects
     */
    private function documentedPhoto(Builder|Relation $projects): ?Media
    {
        $project = $projects
            ->whereHas('page', fn ($query) => $query->published())
            ->whereHas('media')
            ->with(['media' => fn ($query) => $query->orderByPivot('sort_order')])
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->first();

        return $project?->media->firstWhere('pivot.stage', 'after') ?? $project?->media->first();
    }
}
