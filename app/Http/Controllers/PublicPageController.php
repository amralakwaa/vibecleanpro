<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Seo\RedirectResolver;
use App\Seo\SeoHeadResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect as RedirectFacade;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deliberately minimal rendering (see resources/views/seo/page-placeholder
 * .blade.php): this route exists so the SEO systems (indexability, canonical,
 * redirects, structured data, HTTP status codes) are real and testable, not
 * to stand in for the public site's eventual design - that is explicitly a
 * later phase.
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
                return response()->view('seo.page-placeholder', [
                    'page' => $page,
                    'seo' => $this->seoHead->forPage($page),
                ], 200);
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
}
