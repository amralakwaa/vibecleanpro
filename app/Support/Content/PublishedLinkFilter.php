<?php

namespace App\Support\Content;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Unwraps links that point at a page which is not published.
 *
 * Content is written before the page it links to goes live, and a page
 * can be pulled back to Review long after its neighbours were written -
 * either way a visitor must never meet an internal 404. So the anchor is
 * removed at render time and its text stays in the sentence, and the link
 * comes back on its own the day the target is published. Editors change
 * nothing; the body HTML in the CMS is left exactly as written.
 */
class PublishedLinkFilter
{
    /**
     * Published paths, resolved once per request.
     *
     * @var Collection<int, string>|null
     */
    private ?Collection $publishedPaths = null;

    public function filter(?string $html): string
    {
        $html = (string) $html;

        if ($html === '' || ! str_contains($html, '<a ')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '#<a\b[^>]*href="(?<href>[^"]*)"[^>]*>(?<text>.*?)</a>#su',
            fn (array $match): string => $this->isReachable($match['href']) ? $match[0] : $match['text'],
            $html,
        );
    }

    /**
     * External links, anchors and non-page routes (/quote, /contact) are
     * left alone - only a link to a Page this site owns can be checked,
     * and only that kind is unwrapped.
     */
    private function isReachable(string $href): bool
    {
        $path = $this->normalise($href);

        if ($path === null) {
            return true;
        }

        return $this->publishedPaths()->contains($path);
    }

    private function normalise(string $href): ?string
    {
        if ($href === '' || ! str_starts_with($href, '/') || str_starts_with($href, '//')) {
            return null;
        }

        $path = '/'.trim((string) parse_url($href, PHP_URL_PATH), '/');
        $prefix = explode('/', trim($path, '/'))[0] ?? '';

        // Only the routes that serve a Page are ours to judge.
        return in_array($prefix, ['services', 'blog', 'projects', 'areas', 'offers'], true)
            && substr_count(trim($path, '/'), '/') === 1
            ? $path
            : null;
    }

    /**
     * @return Collection<int, string>
     */
    private function publishedPaths(): Collection
    {
        return $this->publishedPaths ??= Page::query()
            ->where('status', PageStatus::Published)
            ->whereIn('type', ['service', 'article', 'project', 'area', 'offer'])
            ->get(['slug', 'type'])
            ->map(fn (Page $page): string => match ($page->type->value) {
                'service' => '/services/'.$page->slug,
                'article' => '/blog/'.$page->slug,
                'project' => '/projects/'.$page->slug,
                'area' => '/areas/'.$page->slug,
                'offer' => '/offers/'.$page->slug,
                default => '/'.$page->slug,
            });
    }
}
