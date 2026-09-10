<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\Page;
use App\Seo\ValueObjects\SimilarPagePair;
use Illuminate\Support\Collection;

/**
 * Deterministic near-duplicate detection - no AI/embeddings. Text is
 * normalized (lowercased, punctuation stripped, Unicode/Arabic-safe) and
 * broken into 3-word shingles; similarity between two pages is the Jaccard
 * index of their shingle sets (intersection / union). This is a standard,
 * well-documented technique for near-duplicate web content detection.
 *
 * Threshold: 0.6 (60% shingle overlap). Chosen as a conservative middle
 * ground from common near-duplicate-detection practice (thresholds in the
 * 0.6-0.8 range are typical); below it, two genuinely different pages
 * about similar topics can share enough boilerplate wording (navigation
 * labels, CTAs, business name) to false-positive, while 0.6+ reliably
 * means the *bodies* of the two pages are substantially the same text,
 * not just similar topics - exactly what an editor needs to know before
 * publishing another near-identical Area page. This is an internal
 * heuristic warning, not a claim about how Google scores duplication.
 *
 * O(n^2) pairwise comparison - fine for the handful of Area pages a real
 * local-SEO site has; never run from a public request (see
 * PerformanceNote in the SEO Dashboard widget).
 */
class DuplicateSimilarityAnalyzer
{
    private const SHINGLE_SIZE = 3;

    private const SIMILARITY_THRESHOLD = 0.6;

    /**
     * @var array<string, Collection<int, SimilarPagePair>>
     */
    private array $cache = [];

    /**
     * @return Collection<int, SimilarPagePair>
     */
    public function findSimilarAreaPages(): Collection
    {
        return $this->findSimilarPages(PageType::Area);
    }

    /**
     * Memoized per instance: PublishingGate calls this once per Area page
     * it evaluates, and the SEO Dashboard evaluates every published page -
     * without this, an O(n^2) scan would re-run O(n) times in the same
     * request. AppServiceProvider binds this class as a singleton so the
     * same instance (and cache) is reused across one request.
     *
     * @return Collection<int, SimilarPagePair>
     */
    public function findSimilarPages(PageType $type): Collection
    {
        return $this->cache[$type->value] ??= $this->computeSimilarPages($type);
    }

    /**
     * @return Collection<int, SimilarPagePair>
     */
    private function computeSimilarPages(PageType $type): Collection
    {
        $pages = Page::query()
            ->published()
            ->where('type', $type)
            ->with('contentBlocks')
            ->get();

        $shingleSets = $pages->mapWithKeys(fn (Page $page) => [$page->id => $this->shingles($this->extractText($page))]);

        $pairs = collect();

        foreach ($pages as $a) {
            foreach ($pages as $b) {
                if ($b->id <= $a->id) {
                    continue;
                }

                $score = $this->jaccard($shingleSets[$a->id], $shingleSets[$b->id]);

                if ($score >= self::SIMILARITY_THRESHOLD) {
                    $pairs->push(new SimilarPagePair($a, $b, $score));
                }
            }
        }

        return $pairs->sortByDesc('score')->values();
    }

    private function extractText(Page $page): string
    {
        $parts = [$page->title];

        foreach ($page->contentBlocks as $block) {
            if (isset($block->data['content']) && is_string($block->data['content'])) {
                $parts[] = strip_tags($block->data['content']);
            }
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<int, string>
     */
    private function shingles(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? '';
        $words = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $n = self::SHINGLE_SIZE;
        $shingles = [];

        for ($i = 0; $i <= count($words) - $n; $i++) {
            $shingles[] = implode(' ', array_slice($words, $i, $n));
        }

        return array_values(array_unique($shingles));
    }

    /**
     * @param  array<int, string>  $a
     * @param  array<int, string>  $b
     */
    private function jaccard(array $a, array $b): float
    {
        if ($a === [] && $b === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique([...$a, ...$b]));

        return $union > 0 ? $intersection / $union : 0.0;
    }
}
