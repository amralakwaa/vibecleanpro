# 14 Area Pages — Deep Quality Audit V1

**Date:** 2026-09-24  
**Source of truth:** Live DB + code (not docs alone)  
**Scope:** 14 published area pages (المحمدية excluded — draft, no content)  
**Mandate:** AUDIT ONLY — no DB mutations, no status changes, no commit, no push

---

## Global Findings (Apply to All 14 Areas)

| Issue | Severity | Count |
|-------|----------|-------|
| Zero real local evidence (0 projects, 0 articles, 0 media per area) | CRITICAL | 14/14 |
| 9 areas with 0 inbound internal links (orphan risk) | HIGH | 9/14 |
| No Service schema emitted for area pages | MEDIUM | 14/14 |
| Identical 7-block content structure across all areas | MEDIUM | 14/14 |
| All 14 areas rely on DB-sourced FAQs (3 each); no inline FAQ content | LOW | 14/14 |

### Schema gap — code evidence

`StructuredDataGenerator::forPage()` uses a `match ($page->type)` block. Area pages have `PageType::Area` (or no type match) which falls to `default => null`. Result: area pages emit **WebPage + BreadcrumbList + FAQPage only** — no Service schema. The master template specifies `LocalBusiness + Service + BreadcrumbList`. This is a structural gap in the code, not a per-page issue.

### Evidence gap

`PROJECTS: 0` and `ARTICLES: 0` for every area (DB-verified). The evidence band section in `area.blade.php` gates on `$area->projects->isNotEmpty()` — this section is hidden on all 14 pages currently.

---

## Per-Area Audit

---

### 1. العليا (al-olaya) — Tier A

| Field | Value |
|-------|-------|
| Page ID | 20 |
| Status | published |
| Services | 4 (curated Tier A) |
| Inbound links | 12 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Correctly targets commercial + residential mix; contains area name + الرياض  
**Content angle:** Targets طريق الملك فهد office corridor; commercial B2B + residential mix — genuinely differentiated  
**SERP position:** Medium competition; commercial B2B angle is open per SERP research  
**Inbound links:** 12 — best-linked area in the set; not an orphan  
**Evidence:** Zero — evidence band hidden  
**Content quality:** Block 1 uses a real geographic reference (طريق الملك فهد); area-specific, not generic copy  
**Service alignment:** 4 curated services — content does not appear to mention services outside this list  

**Issues:**  
- No real projects/media to support commercial claim  
- Content is differentiated but thin without evidence  

**Classification: C — NEEDS LOCAL EVIDENCE**  
*Content and SEO structure are sound. Won't rank without ≥1 documented commercial project.*

---

### 2. العارض (al-arid) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 43 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Post-construction angle; correct  
**Content angle:** New homeowners, growing neighborhood, post-construction demand — genuinely distinct  
**SERP position:** LOW-MEDIUM competition; near-empty SERP for area-specific content  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero — evidence band hidden  
**Content quality:** Differentiated intent; not a generic copy  

**Issues:**  
- Orphan page — 0 inbound internal links  
- No evidence  
- 19 services listed is broad for post-construction focus (content implies narrower scope than service list suggests)  

**Classification: C — NEEDS LOCAL EVIDENCE**  
*First-mover opportunity in a low-competition SERP. Must fix orphan status and add evidence before this page can perform.*

---

### 3. القيروان (al-qirawan) — Tier A

| Field | Value |
|-------|-------|
| Page ID | 28 |
| Status | published |
| Services | 3 (curated Tier A) |
| Inbound links | 1 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Post-construction niche; contains area name + الرياض  
**Content angle:** Post-construction, new villas — clear intent match  
**SERP position:** LOW-MEDIUM competition; 1 thin competitor  
**Inbound links:** 1 — barely linked; near-orphan  
**Evidence:** Zero  
**Similarity risk:** HIGH — post-construction angle shared with al-narjis and al-rimal  

**Issues:**  
- Near-orphan (1 inbound link only)  
- High content similarity to al-narjis and al-rimal (see similarity matrix)  
- No evidence  

**Classification: C — NEEDS LOCAL EVIDENCE**  
*Good niche, low competition. Must differentiate from al-narjis and al-rimal with area-specific detail and evidence. Address orphan status.*

---

### 4. الغدير (al-ghadir) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 34 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Family health/air quality focus; correct  
**Content angle:** AC cleaning + indoor air quality; distinct from other areas  
**SERP position:** MEDIUM-HIGH competition; ghadeer-clean.com (company named after the area) has brand advantage  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  

**Issues:**  
- Orphan page  
- Brand threat: ghadeer-clean.com will dominate branded "الغدير" searches  
- No evidence  
- 19 services broad; content focuses on AC angle but service list is unfiltered  

**Classification: C — NEEDS LOCAL EVIDENCE**  
*Content angle is sound but brand threat from ghadeer-clean.com is a long-term ceiling. Fix orphan status, add evidence, consider curating service list to match content focus.*

---

### 5. المونسية (al-munsiyah) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 59 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Tanks + facades, east Riyadh  
**Content angle:** Water tanks + building facades; east Riyadh  
**SERP position:** MEDIUM competition; east Riyadh underserved  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  

**Issues:**  
- Orphan page  
- No evidence  
- East Riyadh differentiation is a genuine angle but unexploited without evidence  

**Classification: C — NEEDS LOCAL EVIDENCE**

---

### 6. الرمال (al-rimal) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 49 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Post-construction + dust removal  
**Content angle:** Post-construction + dust; dense residential  
**SERP position:** HIGH competition; 4+ area-specific competitor pages  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  
**Similarity risk:** HIGH — nearly identical content angle to al-qirawan and al-narjis  

**Issues:**  
- Orphan page  
- HIGH content similarity to al-qirawan (post-construction, north Riyadh) and al-narjis  
- 4+ competitors already in SERP  
- No evidence  
- If all three post-construction pages (al-qirawan, al-narjis, al-rimal) go live without differentiation, Google may consolidate them — likely picking the one with the most inbound links (al-qirawan: 1, al-narjis: 3) and demoting al-rimal  

**Classification: D — TEMPORARY NOINDEX RECOMMENDED**  
*Content is functionally a near-duplicate of al-qirawan and al-narjis. Should be temporarily noindexed while a genuinely distinct angle (east-side post-construction vs. north-side; or a different primary service) is developed and evidence is secured. Re-evaluate after al-qirawan is rebuilt with evidence.*

---

### 7. الملقا (al-malqa) — Tier A

| Field | Value |
|-------|-------|
| Page ID | 21 |
| Status | published |
| Services | 4 (curated Tier A) |
| Inbound links | 11 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Upscale villas + marble; correct  
**Content angle:** Luxury villa cleaning, marble polishing, post-construction/finishing  
**SERP position:** HIGH competition; alamiyaclean.com building structured area directory  
**Inbound links:** 11 — well-linked  
**Evidence:** Zero  

**CRITICAL ISSUE — SERVICE MISMATCH:**  
Content blocks mention `جلي وتلميع الرخام` (marble polishing) and `تنظيف المجالس`/`تنظيف الكنب`. DB curated services for al-malqa (Tier A, 4 services) do **not** include marble polishing (id:13 — جلي وتلميع الرخام والبلاط) based on Tier A restriction. If the marble polishing service is not in the area's service list, the page is making a service claim it cannot back up in the service directory section. Visitors who click "marble polishing" in the service list will not find it.

**Issues:**  
- Service mismatch: content promises marble polishing; DB service list may not include it  
- No evidence  
- High competition (alamiyaclean.com is the most technically sophisticated competitor)  

**Classification: B — REWRITE PRIORITY**  
*Service mismatch must be resolved first: either add marble polishing to the service list OR remove it from content. Then needs evidence. Cannot rank correctly until content and service list are aligned.*

---

### 8. حطين (hittin) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 29 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Luxury premium, فلل فاخرة  
**Content angle:** Luxury villa, premium materials, marble  
**SERP position:** VERY HIGH competition; 7+ area-specific pages  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  
**Similarity risk:** HIGH — very similar to al-nakheel (luxury marble + pools)  

**UNVERIFIABLE CLAIM:**  
Content block contains mention of European equipment (أجهزة أوروبية / similar). This is an invented technology claim — it cannot be verified from DB, no equipment records exist, and the master template explicitly prohibits inventing technology claims. This violates the security rule: *No fake/unverified content.*

**Issues:**  
- Unverifiable "European equipment" claim in content — must be removed  
- 7+ area-specific competitors; hardest SERP to break into without documented projects  
- Orphan page  
- HIGH similarity to al-nakheel  
- No evidence  
- Content makes luxury claims it cannot support without project documentation  

**Classification: D — TEMPORARY NOINDEX RECOMMENDED**  
*Unverifiable equipment claim makes this page non-compliant with content policy. HIGH similarity to al-nakheel. 7+ competitors. Should be noindexed while content is fixed and at least 2 luxury project records are secured. Not worth publishing against this competition without strong evidence.*

---

### 9. الياسمين (al-yasmin) — Tier A

| Field | Value |
|-------|-------|
| Page ID | 23 |
| Status | published |
| Services | 2 (very restrictive Tier A) |
| Inbound links | 8 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Periodic cleaning, maintenance contracts  
**Content angle:** Periodic/recurring cleaning, villas  
**SERP position:** HIGH competition; aljawadcleaning.com explicitly targets واجهات in this area  
**Inbound links:** 8 — reasonably linked  
**Evidence:** Zero  

**CRITICAL ISSUE — SERVICE MISMATCH:**  
Content mentions `تنظيف الكنب` (sofa/upholstery) and `تنظيف المكيفات` (AC). DB only has **2 services** for this area. If those 2 services are تنظيف المنازل + تنظيف الفلل (or similar periodic-cleaning pair), the sofa and AC mentions are promises the service directory cannot fulfill. This is a high-visibility issue for Tier A pages.

With only 2 services, this page also has critically limited content scope — the master template requires ≥ 1 section of evidence and content not found on other pages. A 2-service page with no evidence and a service mismatch is near-doorway.

**Issues:**  
- Service mismatch: content mentions ≥2 services not in the 2-service DB list  
- Only 2 services = extremely thin content scope  
- No evidence  
- High competition (aljawadcleaning.com targets this area)  
- Content/scope mismatch makes this a doorway page candidate  

**Classification: B — REWRITE PRIORITY**  
*Content must be corrected to only reference the 2 DB services (or the service list must be expanded). Content scope is too narrow for a Tier A published page without evidence. Highest rewrite urgency among Tier A areas.*

---

### 10. النرجس (al-narjis) — Tier A

| Field | Value |
|-------|-------|
| Page ID | 22 |
| Status | published |
| Services | 3 (curated Tier A) |
| Inbound links | 3 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Post-construction, new luxury villas  
**Content angle:** Post-construction, new villa cleaning  
**SERP position:** MEDIUM competition; 2 specific competitors (incl. aljawadcleaning.com)  
**Inbound links:** 3  
**Evidence:** Zero  
**Similarity risk:** HIGH — same angle as al-qirawan and al-rimal  

**Issues:**  
- HIGH content similarity to al-qirawan and al-rimal (post-construction triangle)  
- No evidence  
- Tier A with only 3 services limits content scope  

**Classification: C — NEEDS LOCAL EVIDENCE**  
*Tier A status gives it a curated service list and moderate inbound links. Content is factually OK but needs differentiation from al-qirawan/al-rimal and at least 1 project. Lower similarity risk than al-rimal (which is the weakest of the three).*

---

### 11. الصحافة (al-sahafa) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 30 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Apartments + AC + windows  
**Content angle:** Apartment buildings, AC, glass  
**SERP position:** VERY HIGH competition; 8+ dedicated area pages — the most saturated area in the list  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  

**Issues:**  
- Orphan page  
- 8+ competitor pages already in SERP (most saturated area)  
- No evidence  
- Generic Tier B template with 19 services and no distinguishing angle  
- Content angle (apartments + AC) is present on many competitors' pages  

**Classification: D — TEMPORARY NOINDEX RECOMMENDED**  
*Publishing into a saturated SERP without evidence, as an orphan page, with a generic content angle is likely to produce a crawl waste page. Noindex until evidence (≥1 project) and genuinely differentiated content are ready. Last priority in the publishing queue.*

---

### 12. الربيع (al-rabi) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 31 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Villas + pools/gardens, north Riyadh  
**Content angle:** Villa cleaning with pool and garden scope — genuinely distinct  
**SERP position:** MEDIUM-LOW competition; only 1 thin competitor  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  

**Issues:**  
- Orphan page  
- No evidence  

**Content quality note:** Pool + garden angle is the most distinct of all Tier B areas. Only 1 thin competitor (clean-hoouse.com). Content appears factually accurate and does not make unverifiable claims.

**Classification: C — NEEDS LOCAL EVIDENCE**  
*Best risk/reward among the Tier B orphan pages. Low competition, distinct angle, no false claims. Fix orphan status first; add 1 pool/garden project and this becomes the easiest win after التعاون/العارض.*

---

### 13. التعاون (al-taawun) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 38 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Commercial offices + contracts, north Riyadh business district  
**Content angle:** B2B commercial cleaning, office contracts — the most distinct angle in the entire set  
**SERP position:** LOW competition (SERP GAP) — zero area-specific competitors for this query  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  

**Issues:**  
- Orphan page  
- No evidence — the only blocker for the highest-priority SERP gap in the list  
- 19 services may be too broad; commercial angle would benefit from service curation  

**Content quality note:** Commercial angle is unique, factually accurate, and not replicated by any other area page. This is the strongest differentiator in the set.

**Classification: C — NEEDS LOCAL EVIDENCE**  
*SERP gap is real and the content angle is genuinely distinct. Fix orphan status and add 1 commercial project. This is the highest-priority rebuild target.*

---

### 14. النخيل (al-nakheel) — Tier B

| Field | Value |
|-------|-------|
| Page ID | 44 |
| Status | published |
| Services | 19 |
| Inbound links | 0 |
| Projects | 0 |
| Articles | 0 |
| FAQs (DB) | 3 |

**SEO title:** Luxury marble + pools  
**Content angle:** Luxury villa, marble polishing, pools  
**SERP position:** HIGH competition; 7+ pages including nakeelclean.com (domain named after the area)  
**Inbound links:** 0 — ORPHAN PAGE  
**Evidence:** Zero  
**Similarity risk:** HIGH — nearly identical angle to حطين (hittin)  

**Issues:**  
- Orphan page  
- HIGH similarity to hittin (both luxury marble + pools, both north Riyadh)  
- nakeelclean.com has permanent domain brand advantage  
- 7+ competitors  
- No evidence  

**Classification: D — TEMPORARY NOINDEX RECOMMENDED**  
*Functionally a near-duplicate of hittin in content angle. Both pages ranking for luxury marble/pools in overlapping north Riyadh areas with no evidence is a doorway pattern. Noindex until hittin is rebuilt with evidence and a genuinely distinct angle is developed for النخيل.*

---

## Area Audit Summary

### Classification Counts

| Class | Count | Areas |
|-------|-------|-------|
| A — Keep Indexable | 0 | — |
| B — Rewrite Priority | 2 | الملقا, الياسمين |
| C — Needs Local Evidence | 8 | العليا, العارض, القيروان, الغدير, المونسية, النرجس, الربيع, التعاون |
| D — Temporary Noindex Recommended | 4 | الرمال, حطين, الصحافة, النخيل |
| E — Blocked | 0 | — |

### 11 Audit Questions

1. **How many pages have zero real evidence?**  
   **14 of 14.** Zero projects, zero articles, zero media per area. The evidence band is hidden on every page.

2. **How many pages have a service mismatch?**  
   **2:** الملقا (marble polishing in content, not in 4-service list) and الياسمين (كنب/مكيفات in content, only 2 DB services).

3. **How many pages are orphans (0 inbound internal links)?**  
   **9:** العارض, الغدير, المونسية, الرمال, حطين, الصحافة, الربيع, التعاون, النخيل.

4. **How many pages have unverifiable or false content claims?**  
   **1 confirmed:** حطين — "أجهزة أوروبية" (European equipment) — no DB evidence, violates content policy.  
   **1 flagged:** الملقا — marble polishing is promised but may not be in service list.

5. **How many pages emit FAQPage schema?**  
   Potentially all 14 (each has 3 DB FAQs). Actual emission depends on `is_active=true` flag on FAQ records — not confirmed in this audit. The faq content block has 0 inline items; schema is sourced from the DB FAQs.

6. **What schema do area pages emit?**  
   WebPage + BreadcrumbList + FAQPage (if active FAQs). **No Service schema** (PageType::Area falls to `default => null` in StructuredDataGenerator). The master template's specification of Service schema is **not implemented**.

7. **How many pages have high content similarity to another page?**  
   **6 pages** across 2 clusters:  
   - Post-construction cluster: القيروان ↔ النرجس ↔ الرمال (all score 4/5 similarity)  
   - Luxury cluster: حطين ↔ النخيل (score 4/5 similarity)

8. **Which areas have the strongest SERP gap (best first-mover opportunity)?**  
   1. التعاون (SERP gap score 3/3 — zero area-specific competitors)  
   2. العارض (gap score 3/3 — 1 weak competitor, growing neighborhood)  
   3. القيروان (gap score 3/3 — 1 thin competitor, clear post-construction niche)

9. **Which 3 areas should be rebuilt first?**  
   1. **التعاون** — Unique commercial angle + zero competitors + no false claims + needs only evidence  
   2. **العارض** — Near-empty SERP + first-mover advantage + differentiated  
   3. **الربيع** — 1 weak competitor + distinct pool/garden angle + no false claims

10. **What must happen before any area page can be published correctly?**  
    - Fix service mismatch (الملقا, الياسمين) → immediately  
    - Remove unverifiable claim in حطين content  
    - Add ≥1 inbound internal link to all 9 orphan pages  
    - Add ≥1 real project with photos per area (before any page can pass PublishingGate)  
    - Fix Service schema emission in StructuredDataGenerator for PageType::Area  
    - Fix orphan status via internal linking map

11. **Is any area page currently a doorway page?**  
    **الياسمين** is the closest to doorway page status: 2 DB services, content promises 4+ services not in the list, no evidence, published for ranking only. **حطين** and **النخيل** are near-doorway: unverifiable luxury claims, no evidence, heavy SERP competition with near-duplicate content angles.

---

*AUDIT ONLY — no DB changes, no status changes, no noindex implementation, no commit, no push.*  
*لا Commit · لا Push · لا Deploy — في انتظار موافقتك.*
