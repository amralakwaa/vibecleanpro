@php
    // Only queried if the controller did not already pass one in (see
    // HomeController, which already has it) - `??=` short-circuits, so
    // this never runs a redundant query.
    $businessProfile ??= \App\Models\BusinessProfile::query()->first();

    $whatsappUrl = $businessProfile?->whatsappUrl();
    $phoneUrl = $businessProfile?->phoneUrl();

    $navItems = [
        'خدماتنا' => route('public.services.index'),
        'مناطق التغطية' => route('public.areas.index'),
        'أعمالنا' => route('public.projects.index'),
        'المدونة' => route('public.blog.index'),
        'العروض' => route('public.offers.index'),
        'تواصل معنا' => route('public.contact'),
    ];

    // Empty on purpose: no Legal-type page exists yet (see item 15/16 of
    // the Phase 6 spec) - a fabricated Privacy/Terms link would be worse
    // than none. The footer already omits this row entirely when empty.
    $legalLinks = [];
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

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-text-primary font-sans antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-50 focus:bg-white focus:text-primary-800 focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg">
        تخطَّ إلى المحتوى
    </a>

    <x-public.header :business-profile="$businessProfile" :nav-items="$navItems" :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />

    <main id="main-content" class="pb-24 lg:pb-0">
        {{ $slot }}
    </main>

    <x-public.footer :business-profile="$businessProfile" :nav-items="$navItems" :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />

    <x-public.mobile-cta-bar :whatsapp-url="$whatsappUrl" :phone-url="$phoneUrl" />
</body>
</html>
