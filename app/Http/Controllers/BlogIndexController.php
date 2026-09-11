<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessProfile;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Response;

class BlogIndexController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function index(): Response
    {
        $businessProfile = BusinessProfile::query()->first();

        $published = Article::query()
            ->whereHas('page', fn ($query) => $query->published())
            ->with(['featuredMedia', 'category', 'page']);

        // The single newest article is pulled out as a "featured" spot -
        // only when there is real content to justify it, not a fabricated
        // editorial pick.
        $featured = (clone $published)->orderByDesc('created_at')->first();

        $articles = $published
            ->when($featured, fn ($query) => $query->whereKeyNot($featured->id))
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        $seo = new SeoHeadData(
            title: 'المدونة | '.($businessProfile?->name ?? config('app.name')),
            metaDescription: 'مقالات ونصائح حول التنظيف المنزلي والتجاري في الرياض.',
            canonicalUrl: $this->urlResolver->absoluteUrl('/blog'),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => 'المدونة',
                'description' => 'مقالات ونصائح حول التنظيف المنزلي والتجاري في الرياض.',
                'image' => $businessProfile?->logo?->url(),
                'url' => $this->urlResolver->absoluteUrl('/blog'),
                'type' => 'website',
            ],
            structuredData: [],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('المدونة', null),
            ],
        );

        return response()->view('pages.blog-index', [
            'seo' => $seo,
            'businessProfile' => $businessProfile,
            'featured' => $featured,
            'articles' => $articles,
        ], 200);
    }
}
