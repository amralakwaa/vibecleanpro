# Area Indexability Decisions V1

**Date:** 2026-09-24  
**Source:** DB audit + code review + SERP research + similarity matrix  
**Mandate:** AUDIT ONLY — decisions below are recommendations for owner review, not implemented changes.

---

## Decision Framework

A published area page should be **indexable** only if ALL of the following are true:

1. Content is factually accurate — no unverifiable claims, no service mismatches
2. Content is genuinely distinct from other area pages on the site
3. At least one real piece of local evidence exists (project, testimonial, or documented scope)
4. The page passes the doorway page test (serves real user intent, not keyword-only)
5. Inbound internal links exist (not an orphan)

**Currently:** Zero of the 14 pages meets criteria 3 and 5 simultaneously. However, noindexing all 14 at once would be counterproductive. The recommendation below is surgical: noindex only the pages with active quality problems, while leaving the better-positioned pages indexed as they await evidence.

---

## Final Decisions

| Area | Current | Recommendation | Urgency | Reason |
|------|---------|---------------|---------|--------|
| العليا | indexed | **KEEP INDEXED** | — | 12 inbound links, commercial angle, no false claims |
| الملقا | indexed | **KEEP INDEXED — FIX CONTENT FIRST** | HIGH | Fix service mismatch before next crawl |
| الياسمين | indexed | **KEEP INDEXED — FIX CONTENT FIRST** | CRITICAL | Service mismatch + 2-service scope is near-doorway |
| النرجس | indexed | **KEEP INDEXED** | LOW | 3 inbound links, Tier A, no false claims |
| القيروان | indexed | **KEEP INDEXED** | LOW | Post-construction niche, 1 inbound link, no false claims |
| العارض | indexed | **KEEP INDEXED** | LOW | First-mover SERP gap, differentiated, no false claims |
| الغدير | indexed | **KEEP INDEXED** | LOW | Content accurate, no false claims |
| المونسية | indexed | **KEEP INDEXED** | LOW | East Riyadh, differentiated, no false claims |
| الربيع | indexed | **KEEP INDEXED** | LOW | Distinct angle, low competition, no false claims |
| التعاون | indexed | **KEEP INDEXED** | LOW | Strongest SERP gap, most distinct content |
| الرمال | indexed | **⚠ NOINDEX RECOMMENDED** | MEDIUM | Near-duplicate of القيروان + النرجس; east Riyadh distinction not developed |
| حطين | indexed | **⚠ NOINDEX RECOMMENDED** | HIGH | Unverifiable European equipment claim; near-duplicate of النخيل |
| الصحافة | indexed | **⚠ NOINDEX RECOMMENDED** | MEDIUM | Most saturated SERP (8+ competitors), orphan, generic content |
| النخيل | indexed | **⚠ NOINDEX RECOMMENDED** | MEDIUM | Near-duplicate of حطين, nakeelclean.com brand risk, orphan |

---

## Detailed Rationale

### KEEP INDEXED (10 pages)

#### العليا
- 12 inbound links — not an orphan; strongest link equity of all 14
- Commercial angle is unique in the set
- طريق الملك فهد reference is a real, verifiable geographic landmark
- Medium SERP competition; commercial B2B gap is a genuine opportunity
- **Action needed:** Add evidence. Fix internal linking to ensure commercial-intent pages link here.

#### الملقا
- 11 inbound links — well-linked
- **CONTENT MUST BE FIXED before next crawl:** Remove marble polishing reference from content OR add marble polishing (id:13) to the service list. Leaving the mismatch indexed risks misleading users who follow the content's promise into a service list that doesn't include it.
- After fix: keep indexed, add evidence

#### الياسمين
- 8 inbound links — reasonably linked for Tier A
- **CONTENT MUST BE FIXED URGENTLY:** 2 DB services but content describes 4+ services. This is the most acute service mismatch in the set. The content must be rewritten to either: (a) only describe the 2 DB services, or (b) the service list must be expanded to match what the content promises.
- After fix: keep indexed; thin scope remains a concern until evidence is added

#### النرجس
- 3 inbound links, Tier A, curated 3-service list
- No false claims
- Post-construction angle has HIGH similarity to القيروان and الرمال, but النرجس's "emerging luxury area" framing is a real distinction
- Keep indexed; differentiate from القيروان via content rewrite after evidence is available

#### القيروان
- 1 inbound link — near-orphan, but Tier A with clean content
- No false claims; post-construction niche with clear intent
- **Action needed:** Add at least 1–2 more inbound internal links; add evidence

#### العارض, الغدير, المونسية, الربيع, التعاون
- All orphan pages (0 inbound links) but no false claims, no service mismatches
- Recommended: keep indexed while internal linking is added and evidence is gathered
- Noindexing clean orphan pages without false claims is a net loss — they're not actively harmful, and their potential SERP value (especially التعاون and العارض) is too high to sacrifice

---

### NOINDEX RECOMMENDED (4 pages)

#### الرمال
- 0 inbound links
- PRIMARY REASON: Near-duplicate of both القيروان and النرجس. All three are post-construction, north/east Riyadh villas. الرمال is the weakest: lowest link equity (0), broadest service list (19), least curated.
- SECONDARY REASON: 4+ competitors already in SERP; no evidence; generic content
- **Noindex condition:** Temporary. Re-evaluate when الرمال has a developed east Riyadh angle and ≥1 project documented in this specific area. الرمال's east Riyadh location IS a real differentiator — it is geographically distinct from القيروان/النرجس (north Riyadh) — but the current content does not develop this distinction at all.
- **Noindex method:** Add `<meta name="robots" content="noindex, follow">` to page via admin panel OR set `robots_index = false` in seo_metadata for this page's record

#### حطين
- 0 inbound links
- PRIMARY REASON: Unverifiable "European equipment" claim in content — violates content policy and Google's quality guidelines. This is the only page with a confirmed false/unverifiable claim.
- SECONDARY REASON: Near-duplicate of النخيل; 7+ competitors; no evidence
- **Noindex condition:** Temporary. Must remove the unverifiable claim AND secure ≥2 luxury project records with real photos before re-indexing. حطين requires the highest evidence bar of any page due to the 7+ competitor environment.

#### الصحافة
- 0 inbound links
- PRIMARY REASON: 8+ dedicated competitor pages in SERP (most saturated area in the list); no evidence; orphan; generic 19-service content
- Publishing this page against 8+ established competitors with zero evidence and no distinguishing angle is pure crawl waste
- **Noindex condition:** Temporary. Keep draft-like while evidence is gathered. Re-evaluate when ≥1 project exists AND a genuinely distinct content angle is identified (current content mentions apartments + AC + windows, which every competitor also covers).

#### النخيل
- 0 inbound links
- PRIMARY REASON: Near-duplicate of حطين (luxury marble + pools, north Riyadh). Two near-identical luxury pages competing in the same SERP cluster is counterproductive.
- SECONDARY REASON: nakeelclean.com has permanent domain name advantage for branded searches; 7+ competitors; no evidence
- **Noindex condition:** Temporary. Re-evaluate only after حطين is rebuilt with evidence and its angle is fixed to interior-premium. النخيل should then develop an exterior-premium angle (pools, building facades, outdoor spaces) that is genuinely distinct.

---

## Implementation Notes (for when owner approves)

All noindex changes are seo_metadata record updates — `robots_index = false` for the 4 pages. No page deletion, no redirect, no status change. The page remains published and accessible; it is just excluded from indexing until evidence is ready.

Urgency order:
1. Fix الياسمين content mismatch (CRITICAL — doorway page risk)
2. Fix الملقا content mismatch (HIGH — misleading service claim)
3. Remove حطين European equipment claim (HIGH — false claim in indexed content)
4. Add noindex to الرمال, حطين, الصحافة, النخيل (MEDIUM — quality management)
5. Add internal links to 9 orphan pages (via internal linking map)

---

*AUDIT ONLY — no DB changes, no status changes, no noindex implementation, no commit, no push.*  
*لا Commit · لا Push · لا Deploy — في انتظار موافقتك.*
