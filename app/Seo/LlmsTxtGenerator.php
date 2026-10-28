<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\BusinessProfile;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Builds /llms.txt, the emerging llmstxt.org convention: a single Markdown
 * map of the site for large language models and answer engines, so a model
 * answering a question about the business reaches the right page with an
 * accurate one-line summary instead of guessing from scraped HTML.
 *
 * It is derived from the same published-and-indexable pages the sitemap
 * uses, with authored titles and meta descriptions, so it never invents a
 * claim and stays correct after a fresh migrate + seed. A page Google is
 * told not to index is never offered to an LLM here either.
 */
class LlmsTxtGenerator
{
    public function __construct(
        private readonly IndexabilityEvaluator $indexability,
        private readonly CanonicalResolver $canonical,
        private readonly UrlResolver $urlResolver,
    ) {}

    /**
     * Section order and Arabic heading for each page type that gets its own
     * list. Types absent here (e.g. legal) are grouped under the trust
     * section below.
     *
     * @var array<string, string>
     */
    private const SECTIONS = [
        PageType::Service->value => 'خدمات التنظيف',
        PageType::Area->value => 'مناطق الخدمة في الرياض',
        PageType::Offer->value => 'العروض والباقات',
        PageType::Article->value => 'مقالات ودلائل',
    ];

    /**
     * Listed last under llmstxt.org's "Optional" heading: individual project
     * write-ups are secondary detail a client may skip, unlike the service map.
     */
    private const OPTIONAL_TYPE = PageType::Project;

    public function generate(): string
    {
        $profile = BusinessProfile::query()->first();
        $name = $profile?->displayName() ?: config('app.name');
        $summary = $profile?->identity_statement ?: $profile?->tagline ?: 'شركة تنظيف في الرياض.';

        $pages = Page::query()
            ->with(['seoMetadata', 'pageable'])
            ->published()
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->filter(fn (Page $page) => $this->isEligible($page));

        $lines = [
            "# {$name}",
            '',
            '> '.$this->oneLine($summary),
            '',
            'جميع ما يلي صفحات حقيقية منشورة على '.rtrim(config('app.url'), '/').'، والأوصاف من محتوى الصفحات نفسها.',
        ];

        // Key entry points first: the home and index pages an LLM should
        // reach for an overview before drilling into a single page.
        $lines[] = '';
        $lines[] = '## روابط رئيسية';
        $lines[] = '';
        $lines[] = '- ['.$name.']('.$this->urlResolver->absoluteUrl('/').'): الصفحة الرئيسية.';
        $lines[] = '- [خدمات التنظيف]('.$this->urlResolver->absoluteUrl('/services').'): فهرس كل الخدمات.';
        $lines[] = '- [مناطق الخدمة]('.$this->urlResolver->absoluteUrl('/areas').'): الأحياء التي نخدمها في الرياض.';
        $lines[] = '- [المدونة]('.$this->urlResolver->absoluteUrl('/blog').'): أدلة ومقالات التنظيف.';
        $lines[] = '- [تواصل معنا]('.$this->urlResolver->absoluteUrl('/contact').'): طلب عرض سعر والتواصل.';

        foreach (self::SECTIONS as $type => $heading) {
            $this->appendSection($lines, $heading, $pages->filter(fn (Page $page) => $page->type->value === $type));
        }

        // Everything else that is indexable (About, Trust, legal policies)
        // under one trust-and-policies heading.
        $this->appendSection($lines, 'التعريف والثقة والسياسات', $pages->reject(
            fn (Page $page) => array_key_exists($page->type->value, self::SECTIONS) || $page->type === self::OPTIONAL_TYPE,
        ));

        $this->appendSection($lines, 'Optional', $pages->filter(fn (Page $page) => $page->type === self::OPTIONAL_TYPE));

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<int, string>  $lines
     * @param  Collection<int, Page>  $section
     */
    private function appendSection(array &$lines, string $heading, Collection $section): void
    {
        if ($section->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = "## {$heading}";
        $lines[] = '';

        foreach ($section as $page) {
            $lines[] = $this->linkLine($page);
        }
    }

    private function linkLine(Page $page): string
    {
        $url = $this->urlResolver->urlForPage($page);
        $description = $this->oneLine((string) ($page->seoMetadata?->meta_description ?? ''));

        return $description !== ''
            ? "- [{$page->title}]({$url}): {$description}"
            : "- [{$page->title}]({$url})";
    }

    private function oneLine(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    }

    private function isEligible(Page $page): bool
    {
        if (! $this->indexability->evaluate($page)->indexable) {
            return false;
        }

        return $this->canonical->resolve($page) === $this->urlResolver->urlForPage($page);
    }
}
