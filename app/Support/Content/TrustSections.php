<?php

namespace App\Support\Content;

use App\Models\ContentBlock;
use Illuminate\Support\Collection;

/**
 * Prepares a Trust Center page's editor prose for the reading layout.
 *
 * It never rewrites the editor's words. It does three presentation-only
 * things: lifts a page's opening paragraph out of the body to serve as the
 * hero lede (so the intro is not shown twice), concatenates the remaining
 * rich_text blocks into one reading stream, and gives every top-level
 * heading a stable anchor id so the contents navigation can link to it.
 *
 * Links are run through {@see PublishedLinkFilter} here, exactly as the
 * shared blocks component would, so a trust page renders body links on the
 * same "never a link to an unpublished page" rule as every other page.
 */
class TrustSections
{
    public function __construct(private PublishedLinkFilter $links) {}

    /**
     * @param  Collection<int, ContentBlock>  $blocks  a page's active blocks
     * @return array{lede: ?string, body: string, sections: array<int, array{id: string, label: string}>}
     */
    public function build(Collection $blocks): array
    {
        $richText = $blocks
            ->where('is_active', true)
            ->where('type', 'rich_text')
            ->sortBy('position')
            ->values();

        $lede = null;
        $parts = [];

        foreach ($richText as $index => $block) {
            $content = trim((string) ($block->data['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            // The first block is a lone intro paragraph on every trust page.
            // Lift it into the hero and drop it from the body so the opening
            // line is never printed twice.
            if ($index === 0 && $lede === null && preg_match('#^\s*<p>(?<lede>.*?)</p>\s*$#su', $content, $match)) {
                $lede = trim(strip_tags($match['lede']));

                continue;
            }

            $parts[] = $content;
        }

        $body = $this->links->filter(implode("\n", $parts));

        [$body, $sections] = $this->injectHeadingIds($body);

        return ['lede' => $lede, 'body' => $body, 'sections' => $sections];
    }

    /**
     * Gives every top-level <h2> a stable `section-N` id and returns the
     * ordered list of headings for the contents navigation. Numeric ids
     * are used deliberately - an Arabic heading does not make a safe URL
     * fragment, and the visible number matches the section numbering.
     *
     * @return array{0: string, 1: array<int, array{id: string, label: string}>}
     */
    private function injectHeadingIds(string $html): array
    {
        if (trim($html) === '' || ! str_contains($html, '<h2')) {
            return [$html, []];
        }

        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"?><div id="trust-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $sections = [];
        $headings = [];
        foreach ($dom->getElementsByTagName('h2') as $heading) {
            $headings[] = $heading;
        }

        foreach ($headings as $index => $heading) {
            $label = trim($heading->textContent);

            if ($label === '') {
                continue;
            }

            $id = 'section-'.($index + 1);
            $heading->setAttribute('id', $id);
            $sections[] = ['id' => $id, 'label' => $label];
        }

        $root = $dom->getElementById('trust-root');
        $out = '';

        if ($root !== null) {
            foreach ($root->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }
        }

        return [$out === '' ? $html : $out, $sections];
    }
}
