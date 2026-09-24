# 14 Area Pages — Content Similarity Matrix V1

**Date:** 2026-09-24  
**Method:** Content angle + primary intent + geographic overlap analysis  
**Source:** DB content blocks (first 300 chars intro + block summaries), service lists, SERP research  
**Scale:** 1 = Very distinct | 2 = Some overlap | 3 = Moderate similarity | 4 = High similarity | 5 = Near-duplicate

---

## Similarity Scores (Upper Triangle)

|  | olaya | arid | qirawan | ghadir | munsiyah | rimal | malqa | hittin | yasmin | narjis | sahafa | rabi | taawun | nakheel |
|--|-------|------|---------|--------|----------|-------|-------|--------|--------|--------|--------|------|--------|---------|
| **olaya** | — | 1 | 2 | 1 | 1 | 2 | 2 | 2 | 2 | 2 | 1 | 1 | 2 | 2 |
| **arid** |  | — | 3 | 2 | 1 | 3 | 2 | 1 | 2 | 3 | 2 | 2 | 1 | 1 |
| **qirawan** |  |  | — | 2 | 1 | **4** | 2 | 2 | 2 | **4** | 2 | 2 | 1 | 2 |
| **ghadir** |  |  |  | — | 2 | 2 | 2 | 2 | 2 | 2 | 3 | 2 | 1 | 2 |
| **munsiyah** |  |  |  |  | — | 2 | 1 | 1 | 1 | 1 | 3 | 2 | 1 | 2 |
| **rimal** |  |  |  |  |  | — | 2 | 2 | 2 | **4** | 2 | 2 | 1 | 2 |
| **malqa** |  |  |  |  |  |  | — | 3 | 2 | 2 | 1 | 2 | 1 | 3 |
| **hittin** |  |  |  |  |  |  |  | — | 2 | 2 | 1 | 2 | 1 | **4** |
| **yasmin** |  |  |  |  |  |  |  |  | — | 2 | 2 | 2 | 1 | 2 |
| **narjis** |  |  |  |  |  |  |  |  |  | — | 2 | 2 | 1 | 2 |
| **sahafa** |  |  |  |  |  |  |  |  |  |  | — | 2 | 1 | 2 |
| **rabi** |  |  |  |  |  |  |  |  |  |  |  | — | 1 | 2 |
| **taawun** |  |  |  |  |  |  |  |  |  |  |  |  | — | 1 |
| **nakheel** |  |  |  |  |  |  |  |  |  |  |  |  |  | — |

---

## High-Risk Pairs (Score ≥ 4)

### Cluster 1 — Post-Construction Triangle (Score: 4)

**القيروان ↔ النرجس ↔ الرمال**

All three share:
- Primary intent: تنظيف بعد البناء / فلل جديدة
- Geography: north/north-east Riyadh
- Content structure: identical 7-block template, same process block, same trust block
- Services: restricted (3 each for القيروان/النرجس; 19 but same post-construction framing for الرمال)

**Differentiation present:**
- القيروان: Tier A, 3 curated services, newer residential area framing
- النرجس: Tier A, 3 curated services, emerging luxury area framing — slight upscale distinction
- الرمال: Tier B, 19 services, east Riyadh (الرمال is actually east Riyadh; the other two are north) — this geographic distinction is the only meaningful separator

**Risk:** If all three are indexed simultaneously without differentiated evidence, Google's duplicate content detection may consolidate them. النرجس (3 inbound links) and القيروان (1 inbound link) will outrank الرمال (0 inbound links). الرمال is the most redundant of the three.

**Resolution path:**
1. الرمال → noindex temporarily (it is east Riyadh; develop a distinct east-Riyadh angle when evidence is available)
2. القيروان → keep, develop post-construction angle with project evidence
3. النرجس → keep, develop luxury new-villa angle (distinct from القيروان's "growing neighborhood" framing)

---

### Cluster 2 — Luxury North Riyadh (Score: 4)

**حطين ↔ النخيل**

Both share:
- Primary intent: فلل فاخرة + رخام + مسابح
- Geography: north Riyadh luxury residential
- Content: luxury villas, marble polishing, pools
- Services: both have 19 (Tier B)
- Evidence: both zero

حطين content has the additional problem of an unverifiable "European equipment" claim. النخيل faces nakeelclean.com's domain brand advantage.

**Differentiation possible but weak:**
- حطين: slightly more interior-focused (مجالس, كنب)
- النخيل: slightly more exterior-focused (واجهات, مسابح) — but this distinction does not emerge clearly from current content

**Risk:** Two nearly identical luxury pages, competing against 7+ established competitors each, with no evidence — both will fail to rank and dilute each other's authority.

**Resolution path:**
1. حطين → noindex until European equipment claim removed AND ≥2 luxury project photos secured
2. النخيل → noindex until genuinely distinct angle from حطين developed (e.g., focus on pools/outdoor spaces exclusively) AND evidence secured
3. Rebuild حطين as interior-premium (مجالس + رخام داخلي) and النخيل as exterior-premium (مسابح + واجهات + أحواش) — only valid if real evidence supports each angle

---

## Moderate-Risk Pairs (Score: 3)

### الغدير ↔ الصحافة (Score: 3)

Both are generic Tier B north Riyadh pages with broad 19-service lists and apartment/residential framing. Neither has a strong distinguishing angle in current content. الغدير has AC emphasis; الصحافة has a slightly more apartment-building framing. Both are orphan pages.

**No immediate action needed** (neither is recommended for imminent publishing anyway), but both must develop distinct primary intents before they can differentiate properly.

---

### الملقا ↔ النخيل (Score: 3)

Both target upscale north Riyadh residential: marble polishing, villa cleaning. الملقا is Tier A (4 services, curated) while النخيل is Tier B (19 services). الملقا has 11 inbound links and a stronger existing position; النخيل is an orphan.

**Risk is manageable** if الملقا service mismatch is fixed and its content stays on the curated 4-service scope. النخيل's noindex (recommended) removes the live overlap risk.

---

### الملقا ↔ حطين (Score: 3)

Both upscale north Riyadh luxury villas with marble. الملقا targets post-construction/finishing more explicitly; حطين targets occupied luxury villas. Overlap is moderate.

**Manageable** once حطين is noindexed and الملقا service mismatch is fixed.

---

### المونسية ↔ الصحافة (Score: 3)

Both are generic Tier B pages, both north/east Riyadh, both residential. Neither has a strong distinguishing hook. Both are orphan pages, both have 19 services. The only separator is المونسية's east Riyadh location and tanks/facades focus.

**Not immediately harmful** since both have low inbound links, but must develop distinct primary intents before any evidence is added.

---

## Cluster Risk Summary

| Cluster | Areas | Similarity | Action |
|---------|-------|-----------|--------|
| Post-construction triangle | قيروان, نرجس, رمال | HIGH (4/5) | Noindex رمال; differentiate قيروان vs. نرجس before evidence |
| Luxury north Riyadh | حطين, نخيل | HIGH (4/5) | Noindex both; fix حطين claim; develop distinct angles before re-publishing |
| Generic Tier B residential | غدير, صحافة, مونسية | MODERATE (3/5) | No immediate action; develop distinct angles before publishing |
| Upscale villa | ملقا, نخيل, حطين | MODERATE (3/5) | Resolved by حطين/نخيل noindex + ملقا service fix |

---

## Most Distinct Pages (Safest to Publish)

| Rank | Area | Similarity Max | Why Distinct |
|------|------|---------------|--------------|
| 1 | **التعاون** | 2 | Only commercial/B2B page; no other area targets offices |
| 2 | **الربيع** | 2 | Pool + garden angle not present on any other area page |
| 3 | **العليا** | 2 | Commercial corridor + طريق الملك فهد geographic reference |
| 4 | **المونسية** | 3 | East Riyadh tanks/facades — different geography from all others |
| 5 | **العارض** | 3 | Post-construction in growing neighborhood — distinct from north luxury areas |

---

*AUDIT ONLY — no DB changes, no status changes, no noindex implementation, no commit, no push.*
