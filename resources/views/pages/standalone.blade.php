{{--
    The shared template for every standalone Page (the controller's default
    render arm). It only chooses a body and shares the page variables; each
    body lives in its own partial so its nested directives compile in
    isolation:

      - Trust + Legal  -> pages.partials.trust-center (the Trust Center
        design family: atmospheric hero, wave, numbered reading field with a
        contents rail, related policies, CTA; /trust is the hub of cards).
      - Landing / other -> pages.partials.standalone-blocks (the original
        generic editor-blocks page for campaign/landing pages).

    The page's words are never rewritten here - both partials render DB
    content through the shared block components.
--}}
@php
    $isTrustFamily = in_array($page->type, [\App\Enums\PageType::Trust, \App\Enums\PageType::Legal], true);

    $whatsappUrl = $businessProfile?->whatsappUrl('مرحبًا، لدي سؤال بخصوص: '.$page->title);
    $phoneUrl = $businessProfile?->phoneUrl();
@endphp

<x-layouts.public :seo="$seo" :business-profile="$businessProfile">
    @includeWhen($isTrustFamily, 'pages.partials.trust-center')
    @includeUnless($isTrustFamily, 'pages.partials.standalone-blocks')
</x-layouts.public>
