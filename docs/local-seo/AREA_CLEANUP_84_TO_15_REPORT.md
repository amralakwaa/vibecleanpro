# Area Cleanup Report — 86 → 15

**Date:** 2026-09-24
**Mandate:** e603 — reduce supported areas to exactly 15 per owner decision.
**Status:** COMPLETE (no commit/push — awaiting owner review)

---

## FINAL CONSISTENCY REPORT

```
BEFORE AREA ROWS:     86
PRESERVED:            14
DELETED:              72
CREATED:               1  (المحمدية — new, confirmed by owner)
AFTER:                15

EQUATION:  86 − 72 + 1 = 15  ✓

SUPPORTED AREAS:       15
PUBLISHED AREA PAGES:  14
DRAFT AREA PAGES:       1  (المحمدية)
SITEMAP AREA URLS:     14  (published pages only; المحمدية draft = not in sitemap)

EXTRA AREAS:            0
DUPLICATE NAMES:        0
DUPLICATE SLUGS:        0

PROJECTS DELETED:       0
LEADS DELETED:          0
ORIGINAL MEDIA DELETED: 0

FRESH SEED AREA COUNT: 15  (verified — ProductionContentSeederTest 5/5 pass)
FULL TEST SUITE:       PASS (738/738, 3307 assertions)
```

---

## The 15 Supported Areas — Extracted from DB

| id | Arabic | Slug | Tier | Page id | Page status |
|----|--------|------|------|---------|-------------|
| 1 | العليا | al-olaya | A | 20 | published |
| 2 | الملقا | al-malqa | A | 21 | published |
| 3 | النرجس | al-narjis | A | 22 | published |
| 4 | الياسمين | al-yasmin | A | 23 | published |
| 9 | القيروان | al-qirawan | A | 28 | published |
| 10 | حطين | hittin | B | 29 | published |
| 11 | الصحافة | al-sahafa | B | 30 | published |
| 12 | الربيع | al-rabi | B | 31 | published |
| 15 | الغدير | al-ghadir | B | 34 | published |
| 19 | التعاون | al-taawun | B | 38 | published |
| 24 | العارض | al-arid | B | 43 | published |
| 25 | النخيل | al-nakheel | B | 44 | published |
| 30 | الرمال | al-rimal | B | 49 | published |
| 40 | المونسية | al-munsiyah | B | 59 | published |
| **87** | **المحمدية** | **al-muhammadiyah** | **B** | **177** | **draft** |

`COUNT(*) = 15 · COUNT(DISTINCT slug) = 15 · COUNT(DISTINCT name) = 15`

---

## Note on "84 Areas"

The mandate's "حوالي 84 حيًا" was the owner's approximation. The actual DB count confirmed by git history was **86** (matching production-content.json at commit `2248cc4`). The initial AREA_CLEANUP_84_TO_15_REPORT incorrectly listed "84" as the BEFORE count.

Corrected equation: **86 − 72 + 1 = 15**

---

## المحمدية — 19 Services (NEEDS REVIEW)

The cleanup script attached **all 19 available services** to المحمدية using `syncWithoutDetaching`. This is identical to every other Tier B area (hittin: 19, al-taawun: 19, etc.). The 19 services are:

| id | Service |
|----|---------|
| 1 | تنظيف المنازل |
| 2 | تنظيف الفلل |
| 3 | تنظيف الشقق |
| 4 | تنظيف الكنب |
| 5 | تنظيف المجالس |
| 6 | تنظيف السجاد والموكيت |
| 7 | تنظيف المكاتب والشركات |
| 8 | تنظيف المحلات والمعارض |
| 9 | تنظيف واجهات المباني |
| 10 | تنظيف ما بعد البناء والتشطيب |
| 11 | تنظيف الخزانات |
| 12 | تنظيف المكيفات |
| 13 | جلي وتلميع الرخام والبلاط |
| 14 | تنظيف المسابح |
| 15 | التعقيم والتطهير |
| 16 | مكافحة الحشرات |
| 17 | عقود النظافة الدورية للمنشآت |
| 18 | تنظيف الأحواش والممرات الخارجية |
| 19 | تنظيف الزجاج والنوافذ |

All 19 have `capability_status = available`. Source: `ProductionContentSeeder` pattern — every Tier B area gets all available services (same mechanism as the other 10 Tier B areas). This is a seeder default, not hand-curated per-area.

**Owner decision needed:** Are all 19 services appropriate for المحمدية? Or should the list be restricted? The page is currently **draft** so nothing is public. No change will be made until owner confirms.

---

## 14 Original Areas — Data Integrity

All 14 preserved areas confirmed intact:

| Slug | Page status | SEO | Content blocks | Services |
|------|-------------|-----|----------------|----------|
| al-olaya | published | ✓ | 7 | 4 |
| al-arid | published | ✓ | 7 | 19 |
| al-qirawan | published | ✓ | 7 | 3 |
| al-ghadir | published | ✓ | 7 | 19 |
| al-munsiyah | published | ✓ | 7 | 19 |
| al-rimal | published | ✓ | 7 | 19 |
| al-malqa | published | ✓ | 7 | 4 |
| hittin | published | ✓ | 7 | 19 |
| al-yasmin | published | ✓ | 7 | 2 |
| al-narjis | published | ✓ | 7 | 3 |
| al-sahafa | published | ✓ | 7 | 19 |
| al-rabi | published | ✓ | 7 | 19 |
| al-taawun | published | ✓ | 7 | 19 |
| al-nakheel | published | ✓ | 7 | 19 |

Zero missing pages, zero missing SEO metadata, zero missing content blocks.

---

## Data Safety (Verified from DB)

| Entity | Current count | Deleted by cleanup |
|--------|---------------|-------------------|
| Projects | 47 | **0** |
| Leads | 0 | **0** |
| Media | 528 | **0** |
| Articles | 10 | **0** |
| Offers | 7 | **0** |
| Users | 1 | **0** |

---

## Removed Slug Search — Re-injection Risk

Removed slugs (`al-aqiq`, `diriyah`, `al-yarmouk`, and the 69 others) were searched across:
- `database/` — **0 hits**
- `tests/` — **0 hits**
- `app/` — **0 hits**
- `*.php, *.json` project-wide — found in `scratch_gate_report.php` and `scratch_eval_areas.php` (project-root scratch scripts from a prior dev session, not invoked by any seeder/migration/test)

**Re-injection risk: zero.** A fresh `php artisan migrate:fresh --seed` will produce exactly 15 areas.

---

## What Was Removed

72 areas permanently deleted. All had zero projects, leads, articles, and offers.

**Related records cleaned:**

| Table | Rows removed |
|-------|-------------|
| pages | 70 |
| content_blocks | 490 |
| faqs | 210 |
| seo_metadata | 70 |
| service_area (pivot) | 1,266 |
| internal_links (orphaned) | 5 |

---

## What Was Created

**المحمدية (al-muhammadiyah)** — id 87, page id 177
- Tier B · north-riyadh · sort_order: 25
- 19 services (all available — NEEDS REVIEW above)
- Page status: **draft** (not public, not in sitemap)
- 2 placeholder content blocks (rich_text intro + CTA)
- SEO metadata: `تنظيف حي المحمدية بالرياض | فايب كلين برو`

---

## Files Changed

| File | Change |
|------|--------|
| `database/seeders/content/production-content.json` | 86 → 15 areas; المحمدية added |
| `database/seeders/content/areas-wave1.php` | 10 → 7 entries (removed al-aqiq, diriyah, al-yarmouk) |
| `database/seeders/content/areas-north.php` | 16 → 6 entries (kept: al-sahafa, al-rabi, al-ghadir, al-taawun, al-arid, al-nakheel) |
| `database/seeders/content/areas-east.php` | 22 → 1 entry (kept: al-munsiyah only) |
| `database/seeders/content/areas-west.php` | 11 → 0 entries (empty — no approved west areas) |
| `database/seeders/content/areas-central.php` | 15 → 0 entries (empty — no approved central areas) |
| `database/seeders/content/areas-south.php` | 10 → 0 entries (empty — no approved south areas) |
| `tests/Feature/ProductionContentSeederTest.php` | Count 86 → 15; banban assertion → al-muhammadiyah check |
| `tests/Feature/AreaServicePageTest.php` | wave1Slugs: removed al-aqiq, diriyah, al-yarmouk |
| `tests/Feature/ConversionTrackingTest.php` | al-aqiq → al-sahafa |
| `docs/local-seo/AREA_CLEANUP_84_TO_15_REPORT.md` | This file (corrected from initial version) |

---

## Decisions Still Needed from Owner

1. **المحمدية — 19 services**: Confirm all 19 are appropriate, or specify which to restrict. The page is draft; nothing is public until you publish it.
2. **المحمدية — content**: The page has placeholder content only. Real content requires evidence (real projects in this area) before it can pass the publishing gate.
3. **Commit approval**: Review this report and the `git diff` summary, then give explicit go-ahead before any commit/push.

---

**لا Commit · لا Push · لا Deploy — في انتظار موافقتك.**
