<!DOCTYPE html>
{{--
    Deliberately minimal, unstyled placeholder - this view exists only so
    the SEO systems (indexability, canonical, robots, Open Graph,
    structured data, HTTP status codes) are real and testable end to end.
    It is not the public site's design, which is a later phase.
--}}
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
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
</head>
<body>
    <nav aria-label="breadcrumb">
        @foreach ($seo->breadcrumbs as $crumb)
            @if ($crumb->url)
                <a href="{{ $crumb->url }}">{{ $crumb->label }}</a> /
            @else
                <span>{{ $crumb->label }}</span>
            @endif
        @endforeach
    </nav>

    <h1>{{ $page->title }}</h1>

    @foreach ($page->contentBlocks as $block)
        <section data-block-type="{{ $block->type }}">
            @if ($block->type === 'rich_text')
                {!! $block->data['content'] ?? '' !!}
            @else
                <pre>{{ json_encode($block->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </section>
    @endforeach
</body>
</html>
