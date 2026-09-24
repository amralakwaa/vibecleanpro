# Area Article Mapping V2

**Date:** 2026-09-24  
**Source:** 10 published articles, all currently linked to services but NOT to any area  
**Rule:** Each article linked to 1–3 areas. Area selection based on article topic + area's primary service + reader's decision journey. No article forced into an area where it doesn't serve the reader.

---

## Article Reference

| ID | Title (Arabic) | Primary Service | Excerpt summary |
|----|---------------|-----------------|-----------------|
| A1 | أسعار شركات التنظيف بالرياض: ما الذي يحدد السعر لكل خدمة | فلل + مكاتب + واجهات + بعد البناء | عوامل تحديد السعر لكل نوع خدمة |
| A2 | كيف تختار شركة تنظيف بالرياض | فلل + مكاتب | معايير اختيار شركة تنظيف |
| A3 | قائمة تحقق تسليم العقار بعد التشطيب | بعد البناء | فحص غرفة بغرفة قبل الاستلام |
| A4 | كم تكلفة تنظيف فيلا بالرياض؟ | فلل | عوامل تحديد سعر تنظيف الفيلا |
| A5 | قائمة تنظيف الفيلا دورًا بدور | فلل | ما يشمله التنظيف الشامل |
| A6 | ما الذي يحدد سعر تنظيف الواجهات في الرياض؟ | واجهات | عوامل تكلفة الواجهات |
| A7 | طرق الوصول للواجهات: حبال أم رافعة أم سقالة؟ | واجهات | متى تُستخدم كل طريقة وصول |
| A8 | كيف تحافظ على وضوح زجاج المبنى | واجهات | أسباب خدش الزجاج وكيف تتجنبها |
| A9 | نطاق عمل تنظيف المكاتب: ما يجب أن يكون في الاتفاق | مكاتب | ما يجب أن يتضمنه اتفاق تنظيف المكاتب |
| A10 | تنظيف الأحواش والانترلوك | فلل | ما يضر فواصل الانترلوك وكيف تتعامل معها |

---

## Area → Article Mapping

| Area | Primary Articles | Why |
|------|----------------|-----|
| **التعاون** | A9, A1, A2 | A9 (نطاق مكاتب) — exact match for commercial decision stage. A1 (أسعار) — common pre-purchase question. A2 (كيف تختار) — closing decision support |
| **العارض** | A3, A1 | A3 (قائمة التسليم) — perfect match for post-construction intent. A1 (أسعار) — price research for new homeowners |
| **القيروان** | A3, A4 | A3 (التسليم بعد التشطيب). A4 (تكلفة الفيلا) — post-construction villa owners |
| **الغدير** | A2, A1 | A2 (كيف تختار) — family residential decision. A1 (أسعار) |
| **المونسية** | A6, A7, A8 | A6 (سعر الواجهات). A7 (طرق الوصول). A8 (صيانة الزجاج) — matches facades + glass primary angle |
| **الرمال** | A3, A1 | Same as العارض — post-construction + price research |
| **الملقا** | A4, A5 | A4 (تكلفة فيلا). A5 (قائمة الفيلا) — upscale villa owners researching scope |
| **حطين** | A4, A5 | Same villa angle — interior luxury focus |
| **الياسمين** | A2, A5 | A2 (كيف تختار) — periodic/repeat service decision. A5 (قائمة فيلا) |
| **النرجس** | A4, A3 | A4 (تكلفة فيلا). A3 (تسليم العقار) — new luxury villa owners |
| **الصحافة** | A8, A2 | A8 (زجاج المبنى) — apartment windows. A2 (كيف تختار) |
| **الربيع** | A10, A4 | A10 (أحواش الانترلوك) — pools/gardens primary match. A4 (تكلفة فيلا) |
| **العليا** | A9, A1, A6 | A9 (مكاتب). A1 (أسعار). A6 (واجهات) — commercial + B2B mix |
| **النخيل** | A4, A6 | A4 (تكلفة فيلا). A6 (واجهات) — luxury exterior focus |
| **المحمدية** | — | Draft — no article links until page is published |

---

## Article → Areas Summary

| Article | Linked To (areas) | Usage count |
|---------|------------------|------------|
| A1 | التعاون, العارض, الغدير, العليا | 4 |
| A2 | التعاون, الغدير, الياسمين, الصحافة, العليا | 5 |
| A3 | العارض, القيروان, الرمال, النرجس | 4 |
| A4 | القيروان, الملقا, حطين, النرجس, الربيع, النخيل | 6 |
| A5 | الملقا, حطين, الياسمين | 3 |
| A6 | المونسية, العليا, النخيل | 3 |
| A7 | المونسية | 1 |
| A8 | المونسية, الصحافة | 2 |
| A9 | التعاون, العليا | 2 |
| A10 | الربيع | 1 |

All 10 articles are linked. None left without a function.  
A7 is specialized (access methods for facades) — most useful for المونسية only.  
A10 (courtyard/interlocking) is specific to الربيع pool/garden angle.

---

## Article Slug Status

All 10 articles have blank slugs in DB — slugs need to be generated. The titles already give clear intent; slugs should be derived from them:

| ID | Recommended Slug |
|----|-----------------|
| 1 | cleaning-company-prices-riyadh |
| 2 | how-to-choose-cleaning-company-riyadh |
| 3 | property-handover-checklist-after-finishing |
| 4 | villa-cleaning-cost-riyadh |
| 5 | villa-cleaning-checklist-floor-by-floor |
| 6 | building-facade-cleaning-price-factors-riyadh |
| 7 | facade-access-methods-ropes-vs-crane |
| 8 | building-glass-maintenance-riyadh |
| 9 | office-cleaning-scope-agreement |
| 10 | courtyard-interlocking-cleaning-guide |

No existing public slugs to redirect — all blank.
