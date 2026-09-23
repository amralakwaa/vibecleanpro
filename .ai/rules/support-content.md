---
paths:
  - 'resources/views/pages/standalone.blade.php,resources/views/components/public/trust-*.blade.php,app/Support/Content/Trust*.php'
---

# Support Content

## Trust Center page family
The 8 trust/legal pages (trust, warranty, privacy, terms, complaints, cancellation, service-scope, licenses-compliance) all render through pages/standalone.blade.php — the Trust Center template. Page types: `trust` (trust, warranty) and `legal` (the other 6); both use the SAME unified shell. Composition: x-public.trust-hero (navy surface-atmos, per-page icon+eyebrow from App\Support\Content\TrustPolicies) → wave → reading field (x-public.trust-policy-nav sticky TOC + numbered .prose.prose-legal) → faq/cta via x-public.blocks filtered passes → x-public.trust-related (except hub) → cta. /trust is the hub: x-public.trust-hub renders the 7 policies as x-public.trust-policy-card cards instead of the reading field.

Content is DB-managed rich_text HTML (content_blocks) — NEVER hard-code or rewrite policy prose. App\Support\Content\TrustSections parses the blocks at render: lifts the first <p> into the hero lede, concatenates the rest, injects `section-N` ids on every <h2> for the TOC. Section numbering (01,02…) and segmentation are pure CSS on .prose-legal in app.css (counter + matches TOC). TrustPolicies holds only presentation metadata (icon/eyebrow/blurb/accent) — navigation, not business claims; no fake seals/licences/numbers. New icons added to icon.blade.php: lock, scale, calendar, clipboard, badge-check. Run CSS/JS changes through `npm run build`; run pint/artisan via C:/php84/php.exe (shell php is 8.0).
