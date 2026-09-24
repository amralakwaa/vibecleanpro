# AREA PUBLISHING GATE V1
Generated: 2026-09-24

Before any area page changes status from `draft` to `published`, every check below must pass. Blocks marked **ERROR** are hard blockers — publication is prohibited until resolved. Blocks marked **WARNING** must be consciously accepted with a documented reason.

---

## GATE 1 — Evidence Gate (Hard Requirement)

| Check | Condition | Result |
|-------|-----------|--------|
| Area exists in DB | `Area::where('slug', $slug)->exists()` | **ERROR** if false |
| Area has ≥ 1 linked service | `$area->services->count() >= 1` | **ERROR** if false |
| Area has ≥ 1 published project OR testimonial | projects or testimonials count > 0 | **WARNING** — document reason to proceed |
| All projects in DB have real photos | `$project->media->isNotEmpty()` for each | **ERROR** if any project is photoless |
| No stock/AI-generated photos in projects | Manual review | **ERROR** if found |

---

## GATE 2 — Content Blocks Gate

| Check | Condition | Result |
|-------|-----------|--------|
| Area has exactly 7 content blocks | `$page->contentBlocks->count() == 7` | **WARNING** if not 7 |
| All 5 rich_text blocks are active | `is_active = true` | **WARNING** if any inactive |
| No block mentions a service not in area's service list | Manual review | **ERROR** if found |
| No block contains fake prices | Search for "ريال" + number pattern | **ERROR** if found without owner confirmation |
| No block contains fake timelines ("في ساعتين") | Manual review | **ERROR** if found |
| No block contains invented certifications/guarantees | Manual review | **ERROR** if found |
| Each block has ≥ 1 area-specific sentence | Manual review | **WARNING** if all sentences are generic |
| Area name not used > 3× per block | Count occurrences | **WARNING** if excessive |

---

## GATE 3 — SEO/Title Gate

| Check | Condition | Result |
|-------|-----------|--------|
| Meta title contains area name in Arabic | String match | **ERROR** if missing |
| Meta title contains "الرياض" | String match | **ERROR** if missing |
| Meta title is 50–65 chars | `mb_strlen($title)` | **WARNING** if outside range |
| Meta title does not contain forbidden words | أفضل, رقم 1, الأرخص, مضمون, اتصل الآن, خصم % | **ERROR** if found |
| Meta title is unique across all published pages | Duplicate scan | **ERROR** if duplicate |
| H1 exists and uses area name | Blade template check | **ERROR** if missing |
| H1 is different from meta title | String comparison | **WARNING** if identical |
| Canonical tag present | Template-level | Automatic — verify in source |
| Page included in sitemap | `$page->sitemap_include` | **ERROR** if false |

---

## GATE 4 — Doorway Page Test

Answer all 5 questions. If ANY answer is "No", the page is a doorway page and must not be published.

1. Does this page contain at least one paragraph that is ONLY true for this specific area (not applicable to any other area page)?
2. Would a real visitor searching "[area] + تنظيف" find this page genuinely useful without being redirected or bounced?
3. Is every factual claim on this page verifiable by the owner?
4. Does this page have meaningfully different content from the most similar area page on this site?
5. Would this page make sense to a Google quality rater evaluating it for search quality?

---

## GATE 5 — Technical Gate

| Check | How to verify | Result |
|-------|--------------|--------|
| Page returns HTTP 200 | `curl -I /[slug]` | **ERROR** if not 200 |
| No redirect chain | Check route | **ERROR** if redirect |
| Page speed < 3s LCP (mobile) | PageSpeed Insights | **WARNING** if over |
| Images have alt text | Template review | **WARNING** if missing |
| Internal links from ≥ 2 other pages point to this area | Check INTERNAL_LINKING_MAP | **WARNING** if isolated |

---

## GATE 6 — Near-Duplicate Check

Before publishing, run the near-duplicate check:
1. Compare H1 second-half with all other published area H1s — no matching patterns
2. Compare content block 1 opening sentence — must differ from all other areas
3. Primary service angle must differ from the most-similar area page

**Automated check**: `php artisan tinker` → compare `$page->seoMetadata->meta_title` patterns  
See AREA_SIMILARITY_CONTROL_V1.md for full rules.

---

## Publishing Approval Checklist (to be completed by owner)

- [ ] Gate 1 Evidence: PASS / WAIVED (reason: ___)
- [ ] Gate 2 Content: PASS
- [ ] Gate 3 SEO: PASS
- [ ] Gate 4 Doorway Test: All 5 = YES
- [ ] Gate 5 Technical: PASS
- [ ] Gate 6 Near-Duplicate: PASS
- [ ] Owner confirmation: "I confirm this page reflects real work we do in this area"

**Published by**: ___  
**Date**: ___  
**First project evidence**: ___ (project slug or "waived — first page, evidence pending")
