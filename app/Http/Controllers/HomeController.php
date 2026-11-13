<?php

namespace App\Http\Controllers;

use App\Enums\OfferAvailability;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\BusinessProfile;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Seo\DefaultShareImage;
use App\Seo\StructuredDataGenerator;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\SeoHeadData;
use App\Support\PublicPageCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * The homepage is not backed by a Page model - see UrlResolver, which has
 * no prefix for it, and the Phase 5 report's Home Page Blueprint. It is
 * handled here directly rather than being forced through the generic Page
 * system, but it still produces the same SeoHeadData shape every other
 * public route does, so the layout never needs a homepage special case.
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly UrlResolver $urlResolver,
        private readonly StructuredDataGenerator $structuredData,
        private readonly DefaultShareImage $defaultShareImage,
    ) {}

    public function index(): Response
    {
        // The homepage content and <head> data are identical for every visitor
        // and change only when an editor saves content, so both are cached and
        // flushed on those saves (see PublicPageCache + AppServiceProvider).
        //
        // The rendered content HTML is cached (a string) rather than the query
        // results: Eloquent models cannot be unserialized from cache here by
        // design (config/cache.php 'serializable_classes' => false), and the
        // content carries no CSRF token (that lives in the layout <head>), so
        // caching it never staleness-breaks forms or the tracking beacon.
        // The layout itself still renders per request, keeping the CSRF token
        // and any per-request state correct.
        $profile = BusinessProfile::query()->first();

        $seo = new SeoHeadData(...Cache::remember(
            PublicPageCache::HOME_SEO,
            PublicPageCache::TTL_SECONDS,
            fn (): array => $this->buildSeoArgs($profile),
        ));

        $content = Cache::remember(
            PublicPageCache::HOME_CONTENT,
            PublicPageCache::TTL_SECONDS,
            fn (): string => view('partials.home-content', $this->buildContentData($profile))->render(),
        );

        // The hero photo is the LCP element, so the layout preloads it from
        // <head>. It cannot come from the @push inside the content partial:
        // that partial is rendered to a cached string by its own render()
        // call, which flushes the view factory's stacks before the layout
        // ever reaches @stack. Scalars only - Media is not cacheable here.
        $lcpImage = Cache::remember(
            PublicPageCache::HOME_LCP,
            PublicPageCache::TTL_SECONDS,
            function (): ?array {
                $hero = $this->resolveHeroImage();

                return $hero ? ['url' => $hero->url(), 'srcset' => $hero->srcset()] : null;
            },
        );

        return response()->view('home', [
            'seo' => $seo,
            'businessProfile' => $profile,
            'lcpImage' => $lcpImage,
            'homeContent' => $content,
        ], 200);
    }

    /**
     * Scalar <head> data (title, meta, Open Graph, structured data). Kept free
     * of Eloquent objects so it is safe to cache under serializable_classes.
     *
     * @return array<string, mixed>
     */
    /**
     * The editor's chosen hero photo if one is set (it never claims to be the
     * company's own work), otherwise the featured project's "after" photo,
     * then the most recently completed published project that has one.
     */
    private function resolveHeroImage(): ?Media
    {
        return $this->defaultShareImage->media();
    }

    /**
     * @return Collection<int, Faq>
     */
    private function homeFaqs(): Collection
    {
        return Faq::query()
            ->whereNull('page_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();
    }

    private function buildSeoArgs(?BusinessProfile $profile): array
    {
        $title = $profile
            ? "شركة تنظيف بالرياض — واجهات وفلل ومنازل | {$profile->displayName()}"
            : 'شركة تنظيف بالرياض — واجهات وفلل ومنازل';

        return [
            'title' => $title,
            'metaDescription' => 'شركة تنظيف في الرياض للفلل والشقق والمكاتب والواجهات: نطاق عمل وسعر مكتوبان قبل البدء، فريق مدرّب، أعمال موثّقة بالصور، وضمان إعادة التنظيف خلال 24 ساعة.',
            'canonicalUrl' => $this->urlResolver->absoluteUrl('/'),
            'robotsContent' => 'index, follow',
            'openGraph' => [
                'title' => $title,
                'description' => 'شركة تنظيف في الرياض: نطاق عمل وسعر مكتوبان قبل البدء، وأعمال موثّقة بالصور.',
                'image' => $profile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/'),
                'type' => 'website',
            ],
            // The FAQ block describes the same sitewide questions the page
            // renders (homeFaqs()), so the markup never claims hidden content.
            'structuredData' => [
                ...$this->structuredData->sitewide(),
                ...(($faqs = $this->homeFaqs())->isNotEmpty() ? [$this->structuredData->faqPage($faqs)] : []),
            ],
            'breadcrumbs' => [],
        ];
    }

    /**
     * The homepage content collections, passed to the content partial. Only
     * built on a cache miss (the rendered HTML is what is cached).
     *
     * @return array<string, mixed>
     */
    private function buildContentData(?BusinessProfile $profile): array
    {

        // 'page' is eager-loaded on every list below purely because the
        // view resolves each item's URL through UrlResolver::urlForPage()
        // - without it each card/row costs its own pages query.
        // 'category' labels the service tiles; nothing else on the tile
        // needs a relation beyond the page URL and the photo. Five is the
        // count the services composition is drawn for: one featured tile
        // spanning two rows plus four photo tiles fill the grid exactly,
        // with no orphan tile on a third row; the rest live on /services.
        $services = Service::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['featuredMedia', 'page', 'category'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(5)
            ->get();

        // Every other published service, as plain links under the five tiles,
        // so the homepage reaches the whole catalogue directly instead of only
        // through the /services index.
        $moreServices = Service::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->whereNotIn('id', $services->modelKeys())
            ->with('page')
            ->orderBy('sort_order')
            ->get();

        // A side of Riyadh is linked only when a landing page is published for
        // it - the same rule as AreasIndexController - so none is linked just
        // for existing.
        $sidePages = Page::query()
            ->where('type', PageType::Landing)
            ->where('pageable_type', (new AreaGroup)->getMorphClass())
            ->published()
            ->with('pageable')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Page $page) => $page->pageable instanceof AreaGroup)
            ->values();

        $areas = Area::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->with('page')
            ->withCount('services')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        // Only projects that actually have both a "before" and an "after"
        // photo qualify for the homepage showcase - a project with just one
        // stage photographed has nothing to contrast, so it sits this
        // section out rather than rendering a misleading half-pair.
        $beforeAfterProjects = Project::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->whereHas('media', fn ($query) => $query->where('project_media.stage', 'before'))
            ->whereHas('media', fn ($query) => $query->where('project_media.stage', 'after'))
            ->with(['area', 'services', 'media', 'page'])
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->limit(3)
            ->get();

        // Hero image priority: the image the editor picked in site settings
        // (a licensed illustration from the media library, decorative -
        // it never claims to be the company's own work), otherwise the
        // featured project's "after" photo, then the most recently
        // completed published project that has one. Nothing invented.
        $heroImage = $this->resolveHeroImage();

        // 'area' is eager-loaded because the homepage pull quote prints
        // the customer's area alongside their name when it exists.
        $testimonials = Testimonial::query()
            ->approved()
            ->with('area')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        // Same "currently reachable" rule as /offers (see
        // OffersIndexController): expired offers never appear here.
        // 'services' is eager-loaded because the offer moment states the
        // covered service and derives the honest "instead of" price from
        // it (see OfferPrice) - never from the discount label.
        $offers = Offer::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->where('is_active', true)
            ->with(['featuredMedia', 'page', 'services.featuredMedia'])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Offer $offer) => $offer->availability() !== OfferAvailability::Expired)
            ->sortBy(fn (Offer $offer) => $offer->availability() === OfferAvailability::Active ? 0 : 1)
            ->values()
            ->take(3);

        // Sitewide FAQs (page_id = null - the same scope the Filament FAQ
        // resource already manages, see FaqResource::getEloquentQuery()),
        // not any single page's FAQ block.
        $faqs = $this->homeFaqs();

        // One guide per blog category (the first published article of each of
        // the first three, in the editor's category order), so the homepage
        // reaches the blog without hard-coding any article.
        $guides = ArticleCategory::query()
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(fn (ArticleCategory $category) => Article::query()
                ->where('article_category_id', $category->id)
                ->whereHas('page', fn ($query) => $query->published())
                ->with(['page', 'category'])
                ->orderBy('id')
                ->first())
            ->filter()
            ->values();

        // Real counts for the hero fact strip - the same published sets
        // the /areas and /projects indexes list, so the homepage can only
        // claim what those pages can show.
        $publishedAreas = Area::query()->whereHas('page', fn ($query) => $query->published())->count();
        $publishedProjects = Project::query()->whereHas('page', fn ($query) => $query->published())->count();

        return [
            'businessProfile' => $profile,
            'services' => $services,
            'areas' => $areas,
            'beforeAfterProjects' => $beforeAfterProjects,
            'heroImage' => $heroImage,
            'testimonials' => $testimonials,
            'publishedAreas' => $publishedAreas,
            'publishedProjects' => $publishedProjects,
            'offers' => $offers,
            'faqs' => $faqs,
            'guides' => $guides,
            'moreServices' => $moreServices,
            'sidePages' => $sidePages,
        ];
    }
}
