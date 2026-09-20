# SEO reference library

Strategy documents kept as a permanent reference. **Nothing here is rendered
and nothing here is authoritative over the code.** Where a document and the
application disagree, the application wins — these files record how the
decisions were reached, not what the site currently does.

The live state lives in:

- `database/seeders/content/service-entity-map.php` — the keyword map that is actually enforced (one owner per query, checked by `ServiceEntityMapTest`).
- `database/seeders/content/launch-manifest.json` — what is published, by owner decision.
- `.ai/rules/content.md` — the standing content rules an agent must follow.

## What is here

| Reference | File |
| --- | --- |
| SEO Master Keyword Map (research behind the map) | `master-seo-page-blueprint-v1.md` |
| Competitor analysis | `competitor-gap-analysis-v1.md` |
| Service priority rules + content rules | `service-page-master-template-v1.md` |
| Internal linking strategy | `service-page-master-template-v1.md` + each page file under `service-pages/` |
| Cannibalisation decisions | `service-page-master-template-v1.md`, and the glass/facade split recorded in `service-entity-map.php` |
| Media production map (which asset belongs to which page) | `content-media-production-map-v1.md` + `.csv` |
| Per-page reference builds | `service-pages/` |
| Pre-launch SEO/GEO/local research | `../../VibeCleanPro-SEO-GEO-Research.md` (repository root) |

## Reading the per-page files

`service-pages/` holds the reference build for five pages, written before the
catalogue was finalised. Two carry stale URLs on purpose — they are kept as
written:

- `exterior-surface-cleaning-riyadh.md` shipped as `/services/courtyard-cleaning`.
- `office-commercial-cleaning-riyadh.md` proposed merging offices and commercial
  facilities; the catalogue kept `office-cleaning` and `shop-cleaning` separate,
  and the live office page is broader than this draft.

`glass-cleaning-riyadh.md` is the exception: it was implemented as written, as
its own service, to keep the pane queries away from the facade page.
