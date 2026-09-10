<?php

namespace App\Seo;

use App\Models\Page;
use App\Seo\Enums\CheckSeverity;
use App\Seo\ValueObjects\PageQualityBreakdown;
use App\Seo\ValueObjects\SeoCheckResult;
use Illuminate\Support\Collection;

/**
 * Turns PublishingGate's own checks into a per-dimension completeness
 * percentage - it never re-derives or invents new rules of its own, so it
 * can never disagree with the Publishing Gate or the per-page SEO Audit
 * panel. Deliberately built only from structural/completeness checks
 * (an image exists, a description exists, real related content exists) -
 * nothing here rewards keyword density or content length, so it cannot be
 * used to pressure an editor into keyword-stuffing for a higher number.
 *
 * This is an editorial aid, not a prediction: it must never be labeled
 * "Google Score" or "Google Ranking Score" anywhere it is displayed.
 */
class PageQualityScorer
{
    /**
     * @var array<string, array<int, string>>
     */
    private const DIMENSIONS = [
        'technical' => ['title', 'slug', 'pageable', 'canonical', 'robots', 'redirect_conflict', 'content_empty'],
        'content' => ['seo_title', 'meta_description', 'featured_image', 'image_alt', 'article_author'],
        'local' => ['local_services', 'local_projects', 'local_content_depth', 'local_similarity'],
        'trust' => ['related_content', 'cta'],
        'internalLinking' => ['internal_links'],
    ];

    public function __construct(private readonly PublishingGate $gate) {}

    public function score(Page $page): PageQualityBreakdown
    {
        $checksByKey = collect($this->gate->evaluate($page)->checks)->keyBy('key');

        $dimensionScores = collect(self::DIMENSIONS)
            ->mapWithKeys(fn (array $keys, string $dimension) => [$dimension => $this->dimensionScore($checksByKey, $keys)]);

        $applicable = $dimensionScores->filter(fn (?int $score) => $score !== null);

        return new PageQualityBreakdown(
            overall: $applicable->isEmpty() ? 0 : (int) round($applicable->avg()),
            technical: $dimensionScores['technical'],
            content: $dimensionScores['content'],
            local: $dimensionScores['local'],
            trust: $dimensionScores['trust'],
            internalLinking: $dimensionScores['internalLinking'],
        );
    }

    /**
     * @param  Collection<string, SeoCheckResult>  $checksByKey
     * @param  array<int, string>  $keys
     */
    private function dimensionScore(Collection $checksByKey, array $keys): ?int
    {
        $applicable = $checksByKey->only($keys);

        if ($applicable->isEmpty()) {
            return null;
        }

        $passing = $applicable->filter(fn (SeoCheckResult $check) => $check->severity === CheckSeverity::Pass)->count();

        return (int) round(($passing / $applicable->count()) * 100);
    }
}
