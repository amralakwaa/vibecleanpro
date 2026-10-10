---
paths:
  - 'app/Seo/UrlResolver.php,app/Http/Controllers/PublicPageController.php,app/Http/Controllers/AreasIndexController.php,routes/web.php'
---

# URL policy

## One page, one URL — enforced in both directions
`UrlResolver` owns the whole policy (`/services/{slug}`, `/areas/{slug}`,
`/projects/{slug}`, `/blog/{slug}`, `/offers/{slug}`, `/{slug}` for
standalone). Any exception must be added there, not worked around in a
controller, or canonical and sitemap silently disagree with the route.

## Sides of Riyadh (AreaGroup landing pages)
A side (شمال/شرق/وسط الرياض) is not an Area and must never become one — no
sixteenth fake district. It is a `PageType::Landing` page whose
`pageable_type` is `AreaGroup`, and three pieces keep it on exactly one URL:

- `UrlResolver::pathForPage` returns `/areas/{slug}` for it.
- `PublicPageController::area` falls back to it when no Area owns the slug
  (a real Area always wins, so a side can never shadow a district).
- `PublicPageController::standalone` has `whereNull('pageable_type')`, so the
  root catch-all refuses it. **Do not drop that clause** — without it the
  same page answers at `/{slug}` too.

`AreasIndexController` links a group heading only when such a page is
published; a side earns no URL just by existing in `area_groups`.

Publish a side page only where there is real substance behind it (published
district pages + documented projects). As of Oct 2026: north, east and
central exist; west and south have zero districts and zero projects, so they
have no page — that is deliberate, not an omission.

## Slugs may not contain slashes
`PublishingGate::checkSlug` rejects anything but `[a-z0-9-]`, so a "folder"
slug like `areas/north-riyadh` can be saved as a draft but can never be
published. Route nesting belongs in `UrlResolver`, never in the slug.
