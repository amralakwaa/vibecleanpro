@php
    // Only queried if the controller did not already pass one in (see
    // HomeController, which already has it) - `??=` short-circuits, so
    // this never runs a redundant query.
    $businessProfile ??= \App\Models\BusinessProfile::query()->first();

    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();
    $quoteUrl = route('public.quote');
    // The header's one action never links to the page it is on: the quote
    // page gets no header CTA (the form is the page).
    $headerQuoteUrl = request()->routeIs('public.quote') ? null : $quoteUrl;

    // Pages opt into a transparent-until-scrolled header by setting
    // $headerOverlay - only correct over a full-bleed dark hero image.
    $headerOverlay ??= false;

    // Pages whose whole purpose is a form (Quote, Contact) opt out of the
    // sticky mobile bar with :mobile-bar="false" - a fixed "طلب خدمة"
    // button under the quote form itself is a competing CTA, not help.
    $mobileBar ??= true;

    // "للشركات" points at the existing business-context contact route
    // (see ContactController) - the B2B half of the business was
    // previously unreachable from the main navigation entirely.
    //
    // The About / legal / trust links are all derived from Page lookups that
    // are identical on every page and change only when a Page is saved, so
    // the resolved URL maps are cached and flushed on Page save/delete (see
    // PublicPageCache + AppServiceProvider) - ~9 queries per request become
    // one cache read.
    [$aboutUrl, $legalLinks, $trustLinks] = \Illuminate\Support\Facades\Cache::remember(
        \App\Support\PublicPageCache::LAYOUT_CHROME,
        \App\Support\PublicPageCache::TTL_SECONDS,
        function () {
            $urlResolver = app(\App\Seo\UrlResolver::class);

            // The About page is an editor-created Page (type About); it joins
            // the navigation only once one is actually published, so the menu
            // never links to a 404.
            $aboutPage = \App\Models\Page::query()->where('type', \App\Enums\PageType::About)->published()->first();
            $aboutUrl = $aboutPage ? $urlResolver->urlForPage($aboutPage) : null;

            // Legal pages are editor-created Pages at the reserved slugs
            // "privacy" and "terms". Each link appears only once that page is
            // actually published, so the footer never points at a draft (404).
            // Slug AND type must both match: a Trust page sitting at the slug
            // "privacy" is not the privacy policy, and must never be linked as one.
            $legalPageTitles = [
                'warranty' => ['title' => 'الضمان وشروط الخدمة', 'type' => \App\Enums\PageType::Trust],
                'privacy' => ['title' => 'سياسة الخصوصية', 'type' => \App\Enums\PageType::Legal],
                'terms' => ['title' => 'الشروط والأحكام', 'type' => \App\Enums\PageType::Legal],
            ];
            $legalLinks = collect($legalPageTitles)
                ->map(fn (array $meta, string $slug) => \App\Models\Page::query()
                    ->where('slug', $slug)->where('type', $meta['type'])->published()->first())
                ->filter()
                ->mapWithKeys(fn ($page) => [$legalPageTitles[$page->slug]['title'] => $urlResolver->urlForPage($page)])
                ->all();

            // Trust & policy hub links for the footer column.
            $trustPolicyTitles = [
                'trust'               => ['title' => 'مركز الثقة', 'type' => \App\Enums\PageType::Trust],
                'complaints'          => ['title' => 'الشكاوى والتعويضات', 'type' => \App\Enums\PageType::Legal],
                'cancellation'        => ['title' => 'الإلغاء والمدفوعات', 'type' => \App\Enums\PageType::Legal],
                'service-scope'       => ['title' => 'نطاق الخدمة', 'type' => \App\Enums\PageType::Legal],
                'licenses-compliance' => ['title' => 'الامتثال والتراخيص', 'type' => \App\Enums\PageType::Legal],
            ];
            $trustLinks = collect($trustPolicyTitles)
                ->map(fn (array $meta, string $slug) => \App\Models\Page::query()
                    ->where('slug', $slug)->where('type', $meta['type'])->published()->first())
                ->filter()
                ->mapWithKeys(fn ($page) => [$trustPolicyTitles[$page->slug]['title'] => $urlResolver->urlForPage($page)])
                ->all();

            return [$aboutUrl, $legalLinks, $trustLinks];
        }
    );

    $navItems = array_filter([
        'خدماتنا' => route('public.services.index'),
        'أعمالنا' => route('public.projects.index'),
        'مناطق التغطية' => route('public.areas.index'),
        'من نحن' => $aboutUrl,
        'للشركات' => route('public.contact', ['for' => 'business']),
        'المدونة' => route('public.blog.index'),
        'العروض' => route('public.offers.index'),
        'تواصل معنا' => route('public.contact'),
    ]);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#132A24">

    <title>{{ $seo->title }}</title>
    @if ($seo->metaDescription)
        <meta name="description" content="{{ $seo->metaDescription }}">
    @endif
    <link rel="canonical" href="{{ $seo->canonicalUrl }}">
    <meta name="robots" content="{{ $seo->robotsContent }}">

    <meta property="og:title" content="{{ $seo->openGraph['title'] }}">
    @if ($seo->openGraph['description'])
        <meta property="og:description" content="{{ $seo->openGraph['description'] }}">
    @endif
    @if ($seo->openGraph['image'])
        <meta property="og:image" content="{{ $seo->openGraph['image'] }}">
    @endif
    <meta property="og:url" content="{{ $seo->openGraph['url'] }}">
    <meta property="og:type" content="{{ $seo->openGraph['type'] }}">
    <meta property="og:locale" content="ar_SA">

    <meta name="twitter:card" content="{{ $seo->openGraph['image'] ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo->openGraph['title'] }}">
    @if ($seo->openGraph['description'])
        <meta name="twitter:description" content="{{ $seo->openGraph['description'] }}">
    @endif
    @if ($seo->openGraph['image'])
        <meta name="twitter:image" content="{{ $seo->openGraph['image'] }}">
    @endif

    @foreach ($seo->structuredData as $block)
        <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach

    {{-- Preload hints pushed from individual page components (e.g. hero image). --}}
    @stack('preloads')

    {{-- Preload Leaflet CSS so it is ready before the footer renders; without this
         the browser downloads it mid-body and the map causes a layout shift (CLS). --}}
    <link rel="preload" as="style" href="{{ asset('vendor/leaflet/leaflet.css') }}">

    {{-- Preconnect first so the connection is ready before the font file preloads fire. --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    {{-- Preload the key Arabic-subset font files so they are ready before first paint.
         Without this, async font CSS loads after FCP and font-swap causes CLS.
         IBM Plex Sans Arabic 400 is the LCP element (body text) — high priority.
         Others use fetchpriority="low" to not compete with the hero image. --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin
        href="https://fonts.bunny.net/ibm-plex-sans-arabic/files/ibm-plex-sans-arabic-arabic-400-normal.woff2">
    <link rel="preload" as="font" type="font/woff2" crossorigin fetchpriority="low"
        href="https://fonts.bunny.net/readex-pro/files/readex-pro-arabic-500-normal.woff2">
    <link rel="preload" as="font" type="font/woff2" crossorigin fetchpriority="low"
        href="https://fonts.bunny.net/ibm-plex-sans-arabic/files/ibm-plex-sans-arabic-arabic-700-normal.woff2">
    {{-- Load font CSS asynchronously so it never blocks the first paint.
         The onload swap is the standard JS-free async-CSS pattern; the
         <noscript> fallback covers the rare no-JS case. --}}
    <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700|readex-pro:300,400,500&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700|readex-pro:300,400,500&display=swap"></noscript>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vcp-track" content="{{ route('public.track') }}">
    @php($analytics = app(\App\Support\Analytics\AnalyticsSettings::class))
    @if ($searchConsoleToken = $analytics->searchConsoleToken())
        <meta name="google-site-verification" content="{{ $searchConsoleToken }}">
    @endif
    @if ($analytics->isGa4Active())
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analytics->ga4MeasurementId() }}"></script>
        <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', @json($analytics->ga4MeasurementId()));</script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-text-primary font-sans antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-50 focus:bg-white focus:text-primary-800 focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg">
        تخطَّ إلى المحتوى
    </a>

    <x-public.header
        :business-profile="$businessProfile"
        :nav-items="$navItems"
        :primary-nav="collect($navItems)->except(['المدونة', 'العروض'])->all()"
        :quote-url="$headerQuoteUrl"
        :whatsapp-url="$whatsappUrl"
        :phone-url="$phoneUrl"
        :overlay="$headerOverlay"
    />

    <main id="main-content" @class(['pb-24 lg:pb-0' => $mobileBar])>
        {{ $slot }}
    </main>

    <x-public.footer :business-profile="$businessProfile" :nav-items="$navItems" :legal-links="$legalLinks" :trust-links="$trustLinks" :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />

    @if ($mobileBar)
        <x-public.mobile-cta-bar :quote-url="route('public.quote')" :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
    @endif
</body>
</html>
