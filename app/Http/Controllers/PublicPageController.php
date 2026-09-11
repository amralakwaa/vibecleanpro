<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
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
            ->whereIn('type', [PageType::Trust, PageType::Legal, PageType::Landing])
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
            default => response()->view('pages.standalone', [
                'page' => $page,
                'seo' => $seo,
                'businessProfile' => $businessProfile,
                'faqs' => $faqs,
            ], 200),
        };
    }

    private function renderService(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Service $service */
        $service = $page->pageable;
        $service->load(['featuredMedia', 'category']);

        $areas = $service->areas()->whereHas('page', fn ($query) => $query->published())->get();
        $projects = $service->projects()->whereHas('page', fn ($query) => $query->published())->with('area')->limit(6)->get();

        $related = Service::query()
            ->whereKeyNot($service->id)
            ->whereHas('page', fn ($query) => $query->published())
            ->when($service->service_category_id, fn ($query) => $query->where('service_category_id', $service->service_category_id))
            ->with('page')
            ->limit(3)
            ->get();

        return response()->view('pages.service', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'service' => $service,
            'faqs' => $faqs,
            'areas' => $areas,
            'projects' => $projects,
            'related' => $related,
        ], 200);
    }

    private function renderArea(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Area $area */
        $area = $page->pageable;

        $services = $area->services()->whereHas('page', fn ($query) => $query->published())->with('featuredMedia')->get();
        $projects = $area->projects()->whereHas('page', fn ($query) => $query->published())->with('media')->limit(6)->get();

        $nearbyAreas = Area::query()
            ->whereKeyNot($area->id)
            ->whereHas('page', fn ($query) => $query->published())
            ->when($area->area_group_id, fn ($query) => $query->where('area_group_id', $area->area_group_id))
            ->withCount('services')
            ->limit(6)
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
        ], 200);
    }

    private function renderProject(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Project $project */
        $project = $page->pageable;
        $project->load(['area', 'media', 'services.page']);

        return response()->view('pages.project', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'project' => $project,
            'faqs' => $faqs,
        ], 200);
    }

    private function renderArticle(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Article $article */
        $article = $page->pageable;
        $article->load(['featuredMedia', 'category', 'author']);

        $related = $article->services()->whereHas('page', fn ($query) => $query->published())->with('page')->limit(3)->get();

        return response()->view('pages.article', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'article' => $article,
            'faqs' => $faqs,
            'related' => $related,
        ], 200);
    }

    private function renderOffer(Page $page, SeoHeadData $seo, ?BusinessProfile $businessProfile, $faqs): Response
    {
        /** @var Offer $offer */
        $offer = $page->pageable;
        $offer->load('featuredMedia');

        $related = $offer->services()->whereHas('page', fn ($query) => $query->published())->with('page')->limit(3)->get();

        return response()->view('pages.offer', [
            'page' => $page,
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'offer' => $offer,
            'faqs' => $faqs,
            'related' => $related,
        ], 200);
    }
}
