# Area Internal Linking Map V2

**Date:** 2026-09-24  
**Source:** DB audit — 9 orphan area pages (0 inbound links), 5 with ≥1 inbound link  
**Purpose:** Fix orphan status for all area pages. Add links from service pages, other area pages, and site-wide navigation.

---

## Current Inbound Link Counts

| Area | Current Inbound | Status |
|------|----------------|--------|
| العليا | 12 | ✅ Well-linked |
| الملقا | 11 | ✅ Well-linked |
| الياسمين | 8 | ✅ Linked |
| النرجس | 3 | ⚠ Near-orphan |
| القيروان | 1 | ⚠ Near-orphan |
| العارض | 0 | ❌ ORPHAN |
| الغدير | 0 | ❌ ORPHAN |
| المونسية | 0 | ❌ ORPHAN |
| الرمال | 0 | ❌ ORPHAN |
| حطين | 0 | ❌ ORPHAN |
| الصحافة | 0 | ❌ ORPHAN |
| الربيع | 0 | ❌ ORPHAN |
| التعاون | 0 | ❌ ORPHAN |
| النخيل | 0 | ❌ ORPHAN |
| المحمدية | 0 | Draft — no links needed yet |

---

## Priority Fix Order

Fix in this sequence — highest SERP opportunity first:

1. **التعاون** (SERP gap #1, first area to build)
2. **العارض** (SERP gap #2)
3. **الربيع** (SERP gap, distinct angle)
4. **المونسية** (strong media, distinct east Riyadh)
5. **القيروان** (Tier A, near-orphan → needs 2 more links)
6. **النرجس** (Tier A, near-orphan → needs 3 more links)
7. **الغدير**
8. **الصحافة**
9. **حطين** (noindex recommended — add links only after content fixed)
10. **الرمال** (noindex recommended — add links only after content fixed)
11. **النخيل** (noindex recommended)

---

## Linking Sources

### A. Service Pages → Area Pages

Each service page should link to the 2–3 areas where it is the primary service.

| Service Page | Link to Areas |
|-------------|--------------|
| تنظيف المكاتب والشركات | التعاون (anchor: "تنظيف المكاتب في حي التعاون"), العليا |
| عقود النظافة الدورية | التعاون, العليا |
| تنظيف واجهات المباني | المونسية (anchor: "تنظيف واجهات في المونسية"), العليا |
| تنظيف ما بعد البناء | العارض (anchor: "تنظيف بعد البناء في حي العارض"), القيروان |
| تنظيف الفلل | الملقا, الربيع, حطين |
| تنظيف المسابح | الربيع (anchor: "تنظيف المسابح في حي الربيع"), النخيل |
| تنظيف الأحواش والممرات | الربيع, العارض |
| تنظيف الخزانات | المونسية |
| تنظيف المكيفات | الغدير |
| تنظيف الشقق | الصحافة |
| تنظيف المنازل | الياسمين, الغدير |
| جلي وتلميع الرخام | الملقا, النخيل |

**Anchor text rule:** Use area name + service only. No "أفضل", no superlatives. Pattern: `"[service] في حي [area]"`

---

### B. Area Pages → Nearby Area Pages (Bidirectional)

Link to 2–3 nearby areas in "الأحياء المجاورة" section. Links are bidirectional — each area page lists neighbors that also link back.

| Area | Nearby links (include in page) |
|------|-------------------------------|
| التعاون | الغدير, الصحافة, الربيع |
| العارض | القيروان, النرجس, الملقا |
| القيروان | العارض, النرجس, الملقا |
| الغدير | التعاون, الصحافة, الربيع |
| المونسية | الرمال (east cluster) |
| الرمال | المونسية (east cluster) |
| الملقا | العارض, القيروان, النرجس |
| حطين | الملقا, النرجس, النخيل |
| الياسمين | الملقا, النرجس, الغدير |
| النرجس | العارض, القيروان, الملقا |
| الصحافة | التعاون, الغدير, الربيع |
| الربيع | التعاون, الغدير, الصحافة |
| العليا | التعاون, الملقا |
| النخيل | الملقا, حطين |

---

### C. Homepage Area Section

The homepage should list and link to all 14 published areas (not المحمدية — draft). This provides a baseline 1 inbound link from the highest-authority page for each area.

**Anchor text:** Area name only in Arabic (e.g., "التعاون", "العارض") — no qualifying text.

---

### D. Project Pages → Area Pages

Each project page, when assigned to a primary area, should link to that area's page:  
`"اطلع على خدماتنا في حي [X]"` (anchor = area name)

For projects without confirmed area:  
No area link — link to service page only.

---

### E. Article Pages → Area Pages

| Article | Links to area |
|---------|--------------|
| A9 (مكاتب) | التعاون |
| A3 (تسليم بعد التشطيب) | العارض, القيروان |
| A10 (أحواش) | الربيع |
| A6 (واجهات سعر) | المونسية |

---

## Minimum Target Per Area

After implementing the above:

| Area | Source of links | Expected inbound |
|------|----------------|-----------------|
| التعاون | HP + service (مكاتب + عقود) + neighbor (غدير+ربيع) + A9 + project pages | 7+ |
| العارض | HP + service (بعد البناء + أحواش) + neighbor (قيروان+نرجس) + A3 + project pages | 7+ |
| الربيع | HP + service (مسابح + أحواش) + neighbor (تعاون+غدير) + A10 | 6+ |
| المونسية | HP + service (واجهات + خزانات) + A6 + project pages | 5+ |
| القيروان | HP + service (بعد البناء) + neighbor (عارض+نرجس+ملقا) + A3 | 6+ |
| الغدير | HP + service (مكيفات+منازل) + neighbor (تعاون+ربيع) | 4+ |
| الصحافة | HP + service (شقق+زجاج) + neighbor (تعاون+غدير) | 4+ |
| حطين | HP + neighbor (ملقا+نخيل) | 3+ (post-fix) |
| النخيل | HP + neighbor (ملقا+حطين) + service (مسابح) | 4+ (post-fix) |
| الرمال | HP only for now (noindex period) | 1 |

---

## Implementation Notes

- All `internal_links` records created via admin panel or direct DB insert
- `from_page_id` = source page (service/article/area page)
- `to_page_id` = destination area page
- Do NOT add nofollow — all internal links should pass authority
- Anchor text must be natural Arabic, not keyword-stuffed

**لا Commit · لا Push — في انتظار موافقتك.**
