{{--
    Homepage V2 - Visual Excellence Reset, phase 1.

    Same architecture, same data, same links and the same honesty rules
    as before; what changed is the visual layer. The page now runs on a
    deliberate rhythm of surfaces instead of white/grey alternation:

      1 Hero          deep atmospheric navy + real photograph
      2 Services      tinted light-blue field, image-led tiles
      3 Evidence      white, photograph-led before/after
      4 B2C / B2B     split tonal doors (tinted vs navy)
      5 Offers        the loud moment - blue gradient surface
      6 Trust         clean light: identity, testimonial, principles
      7 Coverage      tinted, compact
      8 FAQ           white, quiet
      9 Decision      deep navy with glow

    Every fact stays CMS-driven (services, prices, offers, projects,
    testimonials, areas, identity, counts) and every optional section
    vanishes when its data is empty. Radius language: tiles 2xl, panels
    3xl, controls xl. Motion: CSS-only reveal + tile hover, both silenced
    by prefers-reduced-motion.
--}}

{{-- The content body is rendered and cached separately (see HomeController).
     This shell only wraps the cached HTML in the public layout, which still
     renders per request so the <head>, CSRF token and nav stay correct. --}}
<x-layouts.public :seo="$seo" :business-profile="$businessProfile" :lcp-image="$lcpImage" :header-overlay="true">
    {!! $homeContent !!}
</x-layouts.public>
