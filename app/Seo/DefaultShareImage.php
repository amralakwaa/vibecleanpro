<?php

namespace App\Seo;

use App\Models\Media;
use App\Models\Project;
use App\Models\SiteSetting;

/**
 * The site's default photograph: the editor's chosen homepage hero, else the
 * "after" photo of the most recent featured published project.
 *
 * The homepage uses it as its LCP hero, and it is the last link in every
 * share-image fallback chain, so a page shared on WhatsApp or X never goes
 * out without a picture just because no logo has been uploaded.
 *
 * Deliberately not memoized: controllers holding it can outlive a request
 * (Laravel keeps the controller on the route), and the homepage result is
 * already cached by PublicPageCache.
 */
class DefaultShareImage
{
    /**
     * The homepage hero: the editor's choice first, then a project photo.
     */
    public function media(): ?Media
    {
        if ($heroMediaId = SiteSetting::get(SiteSetting::HOME_HERO_MEDIA_ID)) {
            if ($hero = Media::query()->find($heroMediaId)) {
                return $hero;
            }
        }

        return $this->projectPhoto();
    }

    /**
     * The share image for pages that have none of their own: a real
     * documented project photo first, so library imagery never stands in
     * for the company's work, and the editor's hero only after that.
     */
    public function url(): ?string
    {
        return ($this->projectPhoto() ?? $this->media())?->url();
    }

    private function projectPhoto(): ?Media
    {
        $heroProject = Project::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->whereHas('media', fn ($query) => $query->where('project_media.stage', 'after'))
            ->with('media')
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->first();

        return $heroProject?->media->firstWhere('pivot.stage', 'after');
    }
}
