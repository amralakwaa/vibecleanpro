# AREA SIMILARITY CONTROL V1
Generated: 2026-09-24

## Why This Matters
84 area pages targeting the same city. Without active similarity control, pages become near-duplicate doorway content — Google deindexes them or collapses them into one result. Every page must have a clearly distinct identity: different primary angle, different evidence, different content block substance.

---

## The 4 Axes of Differentiation

Each area page must be distinct on at least 2 of these 4 axes:

| Axis | What it means |
|------|--------------|
| **Primary service angle** | Which service is the hero of this page (تنظيف بعد البناء vs تنظيف مكاتب vs تنظيف فلل vs تنظيف كنب) |
| **Area character** | What makes this area's cleaning needs different (commercial district, luxury villas, new residential, dense apartments) |
| **Evidence** | The real documented projects are different (before/after scope is area-specific) |
| **Content block substance** | The paragraphs contain area-specific facts, not swapped names |

---

## Prohibited Patterns

### Pattern 1 — Name Swap
Taking a content block from one area and replacing the area name is **prohibited**. If block text reads identically except for the area name, it is a doorway pattern.

### Pattern 2 — Generic Second Half
Meta title endings like "وتنظيف شامل" or "وكافة الخدمات" that apply to every area are **prohibited**. Each title's second clause must be area-specific.

### Pattern 3 — Identical H1 Structure
If 3+ area pages share the same H1 formula (only the area name differs), that is a near-duplicate signal. H1s must vary in structure:
- "تنظيف مكاتب وشركات في حي التعاون بالرياض"
- "تنظيف فلل ومنازل جديدة في حي القيروان بالرياض"
- "تنظيف بعد البناء والواجهات في حي الملقا بالرياض"

These are structurally different even though all reference cleaning + Riyadh.

### Pattern 4 — Boilerplate Intro
Every area's content block 1 must open with an area-specific sentence. Forbidden opening:
> "يُعدّ حي [X] من أرقى أحياء الرياض، ويحتاج إلى خدمات تنظيف احترافية..."

This formula (or close variants) applied to every area is doorway content. Instead, each intro must reference something genuinely specific to that area's character, location, or primary cleaning demand.

---

## Pre-Publish Near-Duplicate Check

Before publishing a new area page, check:

1. **Title similarity**: Does the new meta title have the same second half as any existing title?
2. **H1 structure**: Does the H1 follow the same exact formula as 3+ other area H1s?
3. **Block 1 opening**: Does content block 1 open with the same sentence structure as another area's block 1?
4. **Primary service angle**: Is another area page already using this exact service as its primary angle?

If 2 or more checks flag YES → rewrite before publishing.

---

## Service-Angle Allocation (to avoid cannibalization)

| Primary Angle | Assigned to | Others may reference but not as primary |
|---------------|------------|----------------------------------------|
| تنظيف مكاتب وتجاري (B2B) | **التعاون** (first), العليا | others: secondary mention only |
| تنظيف بعد البناء / تشطيب | **القيروان**, العارض, الملقا | others: can mention, not as hero |
| تنظيف فلل فاخرة + واجهات | **النخيل**, الملقا, حطين | others: villa cleaning = fine, واجهات fاخرة = these 3 |
| تنظيف منازل وشقق عامة | **المونسية**, الرمال, الربيع | broad angle, used for Tier B east/north |
| تنظيف مسابح + حدائق | **الربيع** (primary) | others: may mention as add-on |
| تنظيف كنب + مجالس | **الغدير** (primary) | others: secondary service |
| تنظيف مكيفات | Any area where AC is in service list | Do not claim as primary angle for more than 2 areas |

---

## Cannibalization Matrix (15 Priority Areas)

Cross-check shows no two priority areas have the same primary angle when following this allocation. As new areas are added to the publishing queue, update this table.

| Area A | Area B | Risk | Resolution |
|--------|--------|------|-----------|
| التعاون | العليا | MEDIUM — both commercial | التعاون = B2B offices; العليا = commercial facades + offices (different angle) |
| القيروان | العارض | LOW | القيروان = post-construction; العارض = growing residential (broad) |
| الملقا | النخيل | LOW | الملقا = post-construction + facades; النخيل = luxury فلل + marble |
| حطين | النخيل | MEDIUM — both luxury north | حطين = مجالس + interior; النخيل = exterior + marble + pools |
| الصحافة | الربيع | LOW | both broad residential but different services emphasized |

---

## Ongoing Monitoring

After publishing each new area page:
1. Run the title duplicate scan (dup_scan.php in scratchpad) — confirm 0 exact duplicates
2. Manually compare the new page's H1 and opening paragraph against the 3 most similar area pages
3. After 6 months live: check Google Search Console for keyword cannibalization (same query ranking for 2+ area pages)
