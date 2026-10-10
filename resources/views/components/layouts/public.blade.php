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

    // ['url' => ..., 'srcset' => ...] for the page's LCP image, when the
    // controller knows it. Pages that do not pass one simply preload nothing.
    $lcpImage ??= null;

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
        'الأسعار' => url('/pricing'),
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
    <meta name="theme-color" content="#071228">

    {{-- PERFORMANCE-CRITICAL RESOURCES FIRST: the preload scanner discovers
         them in byte order, so every KB of HTML before these is wasted time
         on a slow connection. CSS + hero image + fonts go before SEO meta. --}}
    {{-- Resource hints come before the inlined stylesheet below. The preload
         scanner reads the document in byte order, so a hint parked behind
         ~100KB of inline CSS text is found late - moving these after it cost
         2.1s of Speed Index in testing. --}}
    @if ($lcpImage)
        <link rel="preload" as="image" href="{{ $lcpImage['url'] }}"
            @if ($lcpImage['srcset'])
                imagesrcset="{{ $lcpImage['srcset'] }}"
                imagesizes="(min-width: 1024px) 60vw, 100vw"
            @endif
            fetchpriority="high">
    @endif
    @stack('preloads')
    <link rel="preload" as="font" type="font/woff2" crossorigin href="/fonts/plex-ar-400.woff2">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="/fonts/readex-ar-500.woff2">

    {{-- Linked, not inlined. Lighthouse calls this the page's only
         render-blocking request and models it at ~602ms, but both ways of
         removing it measured WORSE on PageSpeed: inlining the whole sheet
         scored 94 (Speed Index 2.2s->4.4s) and inlining a Beasties-extracted
         critical subset with the rest async scored 93 (FCP 2.0s->2.3s), against
         95 for simply linking it. The file is 14.6KB over Brotli, edge-cached
         by Cloudflare and multiplexed onto an already-open connection, so
         fetching it costs less than growing the document by 50% and serialising
         the CSS parse into the document parse. Do not "fix" this again without
         measuring. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Self-hosted, subsetted fonts. Replaces fonts.bunny.net: removes a
         third-party DNS+TLS handshake, lets Cloudflare cache the files on the
         same origin, and cuts 298KB of font traffic to 165KB by dropping the
         Arabic Presentation Forms blocks (U+FB50-FDFF, U+FE70-FEFC) and the
         unused Latin ranges. Arabic still shapes correctly: contextual forms
         are reached through the retained GSUB features, not those codepoints.
         font-display:optional keeps CLS at zero - a face that misses first
         paint is skipped for that load rather than swapped in late. --}}
    <style>
    @font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:400;font-display:optional;src:url(/fonts/plex-ar-400.woff2) format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41}
    @font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:500;font-display:optional;src:url(/fonts/plex-ar-500.woff2) format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41}
    @font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:600;font-display:optional;src:url(/fonts/plex-ar-600.woff2) format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41}
    @font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:700;font-display:optional;src:url(/fonts/plex-ar-700.woff2) format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41}
    @font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:400;font-display:optional;src:url(/fonts/plex-la-400.woff2) format('woff2');unicode-range:U+0020-007E,U+00A0,U+00AB,U+00BB,U+00D7,U+2013-2014,U+2018-2019,U+201C-201D,U+2026,U+202F,U+2212,U+FEFF}
    @font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:500;font-display:optional;src:url(/fonts/plex-la-500.woff2) format('woff2');unicode-range:U+0020-007E,U+00A0,U+00AB,U+00BB,U+00D7,U+2013-2014,U+2018-2019,U+201C-201D,U+2026,U+202F,U+2212,U+FEFF}
    @font-face{font-family:'IBM Plex Sans Arabic';font-style:normal;font-weight:600;font-display:optional;src:url(/fonts/plex-la-600.woff2) format('woff2');unicode-range:U+0020-007E,U+00A0,U+00AB,U+00BB,U+00D7,U+2013-2014,U+2018-2019,U+201C-201D,U+2026,U+202F,U+2212,U+FEFF}
    @font-face{font-family:'Readex Pro';font-style:normal;font-weight:400;font-display:optional;src:url(/fonts/readex-ar-400.woff2) format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41}
    @font-face{font-family:'Readex Pro';font-style:normal;font-weight:500;font-display:optional;src:url(/fonts/readex-ar-500.woff2) format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41}
    @font-face{font-family:'Readex Pro';font-style:normal;font-weight:500;font-display:optional;src:url(/fonts/readex-la-500.woff2) format('woff2');unicode-range:U+0020-007E,U+00A0,U+00AB,U+00BB,U+00D7,U+2013-2014,U+2018-2019,U+201C-201D,U+2026,U+202F,U+2212,U+FEFF}
    </style>

    <title>{{ $seo->title }}</title>
    @if ($seo->metaDescription)
        <meta name="description" content="{{ $seo->metaDescription }}">
    @endif
    <link rel="canonical" href="{{ $seo->canonicalUrl }}">
    <meta name="robots" content="{{ $seo->robotsContent }}">

    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/favicon-32x32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="/favicon-16x16.png" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

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
