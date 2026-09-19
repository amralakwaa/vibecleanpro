<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Faq;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use App\Seo\RedirectResolver;
use App\Seo\SeoHeadResolver;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect as RedirectFacade;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders every typed public Page through the Phase 5 design system (see
 * resources/views/pages/*.blade.php and resources/views/components/public/
 * **) - one template per PageType, all sharing the same layout/header/
 * footer/SEO integration.
 *
 * Status code policy:
 * - Published page, on time: 200.
 * - Draft/Review, or Published but scheduled in the future: treated as not
 *   yet public -> 404 (unpublished content is never confirmed to exist).
 * - Archived: 410 Gone - deliberately retired content, not "never existed".
 * - No matching page: check for a redirect at this exact path (old slug or
 *   manual); if found, issue it (default 301); otherwise 404.
 * We never soft-404 (return 200 for "not found" content) and never bounce
 * every miss to the homepage.
 */
class PublicPageController extends Controller
{
    public function __construct(
        private readonly RedirectResolver $redirects,
        private readonly SeoHeadResolver $seoHead,
    ) {}

    public function service(Request $request, string $slug): Response
    {
        return $this->resolve($request, PageType::Service, $slug);
    }

    public function area(Request $request, string $slug): Response
    {
        return $this->resolve($request, PageType::Area, $slug);
    }

    public function project(Request $request, string $slug): Response
    {
        return $this->resolve($request, PageType::Project, $slug);
    }

    public function article(Request $request, string $slug): Response
    {
        return $this->resolve($request, PageType::Article, $slug);
    }

    public function offer(Request $request, string $slug): Response
    {
        return $this->resolve($request, PageType::Offer, $slug);
    }

    public function standalone(Request $request, string $slug): Response
    {
        $page = Page::query()
            ->whereIn('type', [PageType::About, PageType::Trust, PageType::Legal, PageType::Landing])
            ->where('slug', $slug)
            ->first();

        return $this->respond($request, $page);
    }

    private function resolve(Request $request, PageType $type, string $slug): Response
    {
        $page = Page::query()->where('type', $type)->where('slug', $slug)->first();

        return $this->respond($request, $page);
    }

    private function respond(Request $request, ?Page $page): Response
    {
        if ($page) {
            $status = match (true) {
                $page->status === PageStatus::Archived => 410,
                $page->status !== PageStatus::Published => null,
                $page->published_at?->isFuture() => null,
                default => 200,
            };

            if ($status === 200) {
                return $this->render($page);
            }

            if ($status === 410) {
                return response('', 410);
            }
        }

        $redirect = $this->redirects->resolve('/'.ltrim($request->path(), '/'));

        if ($redirect) {
            $this->redirects->recordHit($redirect->redirectId);

            return RedirectFacade::to($redirect->to, $redirect->status);
        }

        abort(404);
    }

    private function render(Page $page): Response
    {
        $seo = $this->seoHead->forPage($page);
        $businessProfile = BusinessProfile::query()->first();
        $faqs = $page->faqs->where('is_active', true);

        return match ($page->type) {
            PageType::Service => $this->renderService($page, $seo, $businessProfile, $faqs),
            PageType::Area => $this->renderArea($page, $seo, $businessProfile, $faqs),
            PageType::Project => $this->renderProject($page, $seo, $businessProfile, $faqs),
            PageType::Article => $this->renderArticle($page, $seo, $businessProfile, $faqs),
            PageType::Offer => $this->renderOffer($page, $seo, $businessProfile, $faqs),
            PageType::About => $this->renderAbout($page, $seo, $businessProfile, $faqs),
            default => response()->view('pages.standalone', [
                'page' => $page,
                'seo' => $seo,
                'businessProfile' => $businessProfile,
                'faqs' => $this->faqsForStandalone($page, $faqs),
            ], 200),
        };
    }

    /**
     * The sitewide FAQ pool (page_id = null - the set FaqResource manages
     * and the homepage shows) reaches a standalone page only when its
     * editor explicitly set the faq block's source to "sitewide" (the FAQ
     * hub). Every other page renders its own FAQs, or nothing - a guarantee
     * or legal page never inherits general questions by accident.
     */
    private function faqsForStandalone(Page $page, $faqs)
    {
        $usesSitewidePool = $page->contentBlocks->contains(
            fn ($block) => $block->type === 'faq' && $block->is_active && ($block->data['source'] ?? 'page') === 'sitewide'
        );

        if (! $usesSitewidePool) {
            return $faqs;
        }

        return Faq::query()->whereNull('page_id')->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * About = company identity page. Every fact comes from the business
     * profile (identity, founder) and the team_members table; the Page
     * itself contributes title, SEO, optional extra blocks and FAQs.
     * Counts are the same published sets the /areas and /projects indexes
     * list, so the page can only claim what those pages can show.
     */
    private function renderAbout(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        $businessProfile?->load('founderPhoto');

        $team = $businessProfile?->show_team
            ? TeamMember::query()->visible()->with('photo')->get()
            : collect();

        $publishedAreas = Area::query()->whereHas('page', fn ($query) => $query->published())->count();
        $publishedProjects = Project::query()->whereHas('page', fn ($query) => $query->published())->count();

        return response()->view('pages.about', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'faqs' => $faqs,
            'team' => $team,
            'publishedAreas' => $publishedAreas,
            'publishedProjects' => $publishedProjects,
        ], 200);
    }

    private function renderService(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Service $service */
        $service = $page->pageable;
        $service->load(['featuredMedia', 'category']);

        // 'page' is eager-loaded on every list on this page because the
        // view resolves each item's URL through UrlResolver::urlForPage().
        $areas = $service->areas()
            ->whereHas('page', fn ($query) => $query->published())
            ->with('page')
            ->get();

        // 'area' and 'media' both eager-loaded here so the view never
        // triggers a query per project to decide before/after vs. after-only
        // rendering (see pages/service.blade.php).
        $projects = $service->projects()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['area', 'media', 'page'])
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->limit(6)
            ->get();

        $related = Service::query()
            ->whereKeyNot($service->id)
            ->whereHas('page', fn ($query) => $query->published())
            ->when($service->service_category_id, fn ($query) => $query->where('service_category_id', $service->service_category_id))
            ->with('page')
            ->limit(3)
            ->get();

        // Only offers that are Active right now (never Scheduled, never
        // Expired) - unlike /offers and the homepage, which also surface
        // Scheduled ones, a Service page only ever promises what a visitor
        // can actually act on today. This mirrors Offer::availability()'s
        // Active branch exactly (is_active, starts_at not in the future,
        // ends_at not in the past) so the two can never disagree, and it is
        // applied in SQL - not fetch-then-filter in PHP - so the row scan
        // is never unbounded and a later-sorted Active offer can never be
        // pushed out by an earlier take().
        $offers = $service->offers()
            ->whereHas('page', fn ($query) => $query->published())
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with(['featuredMedia', 'page'])
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        // 'area' eager-loaded because the pull quote prints the customer's
        // area next to their name when the relation exists.
        $testimonials = $service->testimonials()
            ->approved()
            ->with('area')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        // When an editor has already placed a manual related_content block
        // (see blocks.blade.php), the automatic "خدمات ذات صلة" fallback
        // below must not render a second, duplicate related-services
        // section on the same page.
        $hasManualRelatedBlock = $page->contentBlocks
            ->contains(fn ($block) => $block->type === 'related_content' && $block->is_active);

        return response()->view('pages.service', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'service' => $service,
            'faqs' => $faqs,
            'areas' => $areas,
            'projects' => $projects,
            'related' => $related,
            'offers' => $offers,
            'testimonials' => $testimonials,
            'hasManualRelatedBlock' => $hasManualRelatedBlock,
        ], 200);
    }

    private function renderArea(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Area $area */
        $area = $page->pageable;

        // The service_area pivot's is_active flag is a separate "is this
        // link currently on" switch from the Service's own Page status -
        // both must hold for the pairing to be real right now.
        // 'page' is eager-loaded on every list below because the view
        // resolves each item's URL through UrlResolver::urlForPage().
        // 'group' names the local context in the hero; 'category' groups
        // the service rows on the page.
        $area->load('group');

        $services = $area->services()
            ->wherePivot('is_active', true)
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['featuredMedia', 'page', 'category'])
            ->orderBy('sort_order')
            ->get();

        // 'media' eager-loaded so the view never triggers a query per
        // project to decide before/after vs. after-only rendering (see
        // pages/area.blade.php and the identical rule in renderService()).
        $projects = $area->projects()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['media', 'page'])
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->limit(6)
            ->get();

        $nearbyAreas = Area::query()
            ->whereKeyNot($area->id)
            ->whereHas('page', fn ($query) => $query->published())
            ->when($area->area_group_id, fn ($query) => $query->where('area_group_id', $area->area_group_id))
            ->with('page')
            ->withCount('services')
            ->limit(6)
            ->get();

        // Only offers that are Active right now - same SQL-level rule as
        // renderService() (never Scheduled, never Expired; see that
        // method's comment for why this is done in SQL, not fetch-then-
        // filter in PHP).
        $offers = $area->offers()
            ->whereHas('page', fn ($query) => $query->published())
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with(['featuredMedia', 'page'])
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        $testimonials = $area->testimonials()
            ->approved()
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $articles = $area->articles()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['featuredMedia', 'category', 'page'])
            ->limit(3)
            ->get();

        return response()->view('pages.area', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'area' => $area,
            'faqs' => $faqs,
            'services' => $services,
            'projects' => $projects,
            'nearbyAreas' => $nearbyAreas,
            'offers' => $offers,
            'testimonials' => $testimonials,
            'articles' => $articles,
        ], 200);
    }

    private function renderProject(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Project $project */
        $project = $page->pageable;
        $project->load(['area.page', 'media']);

        // Only a genuinely reachable Area is ever linked - $project->area
        // itself always renders as plain text (see pages/project.blade.php).
        $areaPage = $project->area?->page;
        $linkedArea = $areaPage && $areaPage->status === PageStatus::Published && (! $areaPage->published_at || $areaPage->published_at->isPast())
            ? $project->area
            : null;

        $relatedServices = $project->services()->whereHas('page', fn ($query) => $query->published())->with('page')->get();

        $relatedProjects = $project->area
            ? Project::query()
                ->whereKeyNot($project->id)
                ->where('area_id', $project->area_id)
                ->whereHas('page', fn ($query) => $query->published())
                ->with(['media', 'page'])
                ->limit(3)
                ->get()
            : collect();

        return response()->view('pages.project', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'project' => $project,
            'faqs' => $faqs,
            'linkedArea' => $linkedArea,
            'relatedServices' => $relatedServices,
            'relatedProjects' => $relatedProjects,
        ], 200);
    }

    private function renderArticle(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Article $article */
        $article = $page->pageable;
        $article->load(['featuredMedia', 'category', 'author']);

        $related = $article->services()->whereHas('page', fn ($query) => $query->published())->with('page')->limit(3)->get();
        $relatedAreas = $article->areas()->whereHas('page', fn ($query) => $query->published())->with('page')->limit(3)->get();

        $relatedArticles = Article::query()
            ->whereKeyNot($article->id)
            ->whereHas('page', fn ($query) => $query->published())
            ->when($article->article_category_id, fn ($query) => $query->where('article_category_id', $article->article_category_id))
            ->with(['featuredMedia', 'category', 'page'])
            ->limit(3)
            ->get();

        // No direct Article -> Project relation exists (and per the Phase
        // 6 report, item 25, none is being added) - "related" projects
        // here are purely inferred from the topics (Service/Area) this
        // Article already shares real relations with, never stored.
        $topicServiceIds = $article->services()->pluck('services.id');
        $topicAreaIds = $article->areas()->pluck('areas.id');

        $relatedProjects = ($topicServiceIds->isEmpty() && $topicAreaIds->isEmpty())
            ? collect()
            : Project::query()
                ->whereHas('page', fn ($query) => $query->published())
                ->where(function ($query) use ($topicServiceIds, $topicAreaIds) {
                    $query->when($topicAreaIds->isNotEmpty(), fn ($q) => $q->whereIn('area_id', $topicAreaIds));
                    $query->when($topicServiceIds->isNotEmpty(), fn ($q) => $q->orWhereHas(
                        'services',
                        fn ($sq) => $sq->whereIn('services.id', $topicServiceIds)
                    ));
                })
                ->with(['media', 'page', 'area'])
                ->limit(3)
                ->get();

        // Reading time is computed from the article's own active rich_text
        // blocks (tags stripped, ~200 words a minute) - never a stored guess.
        // Short pieces get no figure at all rather than a meaningless "1".
        $words = $page->contentBlocks
            ->filter(fn ($block) => $block->is_active && $block->type === 'rich_text')
            ->sum(fn ($block) => preg_match_all('/[\p{L}\p{N}]+/u', strip_tags((string) ($block->data['content'] ?? ''))));
        $readingMinutes = $words >= 150 ? (int) max(1, ceil($words / 200)) : null;

        return response()->view('pages.article', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'article' => $article,
            'faqs' => $faqs,
            'related' => $related,
            'relatedAreas' => $relatedAreas,
            'relatedArticles' => $relatedArticles,
            'relatedProjects' => $relatedProjects,
            'readingMinutes' => $readingMinutes,
        ], 200);
    }

    private function renderOffer(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Offer $offer */
        $offer = $page->pageable;
        $offer->load('featuredMedia');

        // 'featuredMedia' on the covered services: an offer without its own
        // picture shows the service's photograph instead.
        $offerServices = $offer->services()->whereHas('page', fn ($query) => $query->published())->with(['page', 'featuredMedia'])->get();
        $offerAreas = $offer->areas()->whereHas('page', fn ($query) => $query->published())->with('page')->get();

        return response()->view('pages.offer', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'offer' => $offer,
            'faqs' => $faqs,
            'related' => $offerServices,
            'offerServices' => $offerServices,
            'offerAreas' => $offerAreas,
        ], 200);
    }
}
