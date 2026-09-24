# Project Location vs. Area SEO Support — Audit V1

**Date:** 2026-09-24  
**Purpose:** Distinguish `project.area_id` (actual location) from "SEO support for area page" (strategic)  
**Critical rule:** `area_id` = confirmed physical location only. SEO support is a separate relationship.

---

## Background — What Went Wrong

In the previous implementation session, `area_id` was set for all 47 projects based on service-type matching:
- Office-cleaning projects → التعاون
- Facade projects → المونسية
- Villa projects → الملقا
etc.

**This was wrong.** All 47 projects have `NEIGHBORHOOD/CITY/AREA_FK = NONE` (no confirmed location in the DB). Setting `area_id` based on SEO strategy creates a **false geographic claim** — the system would have displayed these projects as "work done in التعاون" when the location is actually unknown.

**Corrective action taken:** All 47 project `area_id` values reverted to `NULL` (2026-09-24).

---

## All 47 Projects — Location Audit

| ID | Title | Old area_id (erroneous) | ACTUAL_LOCATION_CONFIRMED | Correct area_id | Notes |
|----|-------|------------------------|--------------------------|-----------------|-------|
| P1 | تنظيف شامل بعد التشطيب — وحدة سكنية | العارض | NO | NULL | Post-construction type, no location in DB |
| P2 | تنظيف واجهة زجاجية لمبنى تجاري | المونسية | NO | NULL | Facade type, no location in DB |
| P3 | تنظيف شامل لفيلا سكنية — أعمال كاملة | الملقا | NO | NULL | Villa type, no location in DB |
| P4 | تنظيف أحواش ومداخل فيلا — إنترلوك وأرضيات | الربيع | NO | NULL | Courtyard type, no location in DB |
| P5 | تنظيف أحواش وممرات خارجية — قبل الاستخدام | الربيع | NO | NULL | Outdoor paths, no location in DB |
| P6 | تنظيف بعد البناء لمشروع سكني — أرضيات ومرافق | العارض | NO | NULL | Post-construction, no location in DB |
| P7 | تنظيف حمامات وأرضيات — منزل وبيئة عمل | الياسمين | NO | NULL | Residential, no location in DB |
| P8 | تنظيف واجهة مبنى — طوابق متعددة | المونسية | NO | NULL | Facade, no location in DB |
| P9 | تنظيف مكاتب إدارية — بيئة عمل احترافية | التعاون | NO | NULL | Office, no location in DB |
| P10 | تنظيف واجهة زجاجية لمبنى — محيط تجاري | المونسية | NO | NULL | Facade, no location in DB |
| P11 | تنظيف زجاج الواجهات الداخلية والخارجية | الصحافة | NO | NULL | Glass, no location in DB |
| P12 | تنظيف مكاتب وصالة استقبال | التعاون | NO | NULL | Office, no location in DB |
| P13 | تنظيف أحواش وممرات فيلا — بلاط وجرانيت | الربيع | NO | NULL | Courtyard, no location in DB |
| P14 | تنظيف حوش ومدخل فيلا | الربيع | NO | NULL | Courtyard, no location in DB |
| P15 | تنظيف دورات مياه في منشأة خدمية | التعاون | NO | NULL | Restroom, no location in DB |
| P16 | تنظيف فيلا سكنية — داخلي شامل | الياسمين | NO | NULL | Villa, no location in DB |
| P17 | تنظيف مكاتب إدارية — طابق كامل | التعاون | NO | NULL | Office, no location in DB |
| P18 | تنظيف الارتفاعات والأسقف الداخلية | حطين | NO | NULL | Interior height, no location in DB |
| P19 | تنظيف زجاج خارجي لمبنى تجاري | المونسية | NO | NULL | Facade, no location in DB |
| P20 | تنظيف وجلي أرضيات رخام — فيلا | الملقا | NO | NULL | Marble, no location in DB |
| P21 | تنظيف ممرات وأرضيات مبنى إداري | التعاون | NO | NULL | Office corridors, no location in DB |
| P22 | تنظيف بعد التشطيب — مشروع سكني صغير | القيروان | NO | NULL | Post-construction, no location in DB |
| P23 | تنظيف مكتب — فضاء مفتوح | التعاون | NO | NULL | Office, no location in DB |
| P24 | تنظيف مواقف السيارات في منشأة تجارية | التعاون | NO | NULL | Parking, no location in DB |
| P25 | تنظيف مجمع تجاري ومحلات | التعاون | NO | NULL | Commercial complex, no location in DB |
| P26 | تنظيف واجهة مبنى سكني | الصحافة | NO | NULL | Facade, no location in DB |
| P27 | تنظيف مرافق منشأة تجارية — ممرات مشتركة | التعاون | NO | NULL | Commercial facility, no location in DB |
| P28 | تنظيف زجاج خارجي لمبنى | الصحافة | NO | NULL | Glass, no location in DB |
| P29 | تنظيف مطبخ شامل — منزل سكني | الياسمين | NO | NULL | Kitchen, no location in DB |
| P30 | تنظيف حمامات في وحدة سكنية | الصحافة | NO | NULL | Bathroom, no location in DB |
| P31 | تنظيف واجهة مبنى — خارجي 1 | المونسية | NO | NULL | Facade, no location in DB |
| P32 | تنظيف واجهة زجاجية زرقاء — مبنى تجاري | المونسية | NO | NULL | Facade, no location in DB |
| P33 | تنظيف الثريات والإضاءة الداخلية — فيلا | حطين | NO | NULL | Interior luxury, no location in DB |
| P34 | تنظيف مكاتب — بيئة عمل | التعاون | NO | NULL | Office, no location in DB |
| P35 | جلي وتلميع رخام — أرضيات وأسطح | الملقا | NO | NULL | Marble, no location in DB |
| P36 | تنظيف واجهة مبنى — خارجي 2 | المونسية | NO | NULL | Facade, no location in DB |
| P37 | تنظيف مكتب — ما بعد التشطيب | التعاون | NO | NULL | Office post-fitout, no location in DB |
| P38 | تنظيف منزل شامل مع التسليم | الياسمين | NO | NULL | Residential, no location in DB |
| P39 | تنظيف بعد البناء — وحدة سكنية | القيروان | NO | NULL | Post-construction, no location in DB |
| P40 | تنظيف فيلا | النرجس | NO | NULL | Villa, no location in DB |
| P41 | تنظيف عام | الغدير | NO | NULL | General, no location in DB |
| P42 | تنظيف حوش ومداخل فيلا | الربيع | NO | NULL | Courtyard, no location in DB |
| P43 | تنظيف واجهة مبنى — خارجي 3 | المونسية | NO | NULL | Facade, no location in DB |
| P44 | تنظيف وتلميع أرضيات | الملقا | NO | NULL | Floor polish, no location in DB |
| P45 | تنظيف دورات مياه منشأة | التعاون | NO | NULL | Facility restroom, no location in DB |
| P46 | تنظيف مكاتب — مبنى إداري | التعاون | NO | NULL | Office building, no location in DB |
| P47 | تنظيف فيلا سكنية | النرجس | NO | NULL | Villa, no location in DB |

---

## Summary

| Metric | Count |
|--------|-------|
| Total projects | 47 |
| Projects with confirmed actual location | **0** |
| Projects assigned false location (now reverted) | **47 → 0** |
| Projects with NULL area_id (correct state) | **47** |

---

## What area_id Should Mean Going Forward

`projects.area_id` is a **location field** — it has companion columns: `location_source`, `location_confidence`, `location_evidence_type`, `location_status`, `verified_at`, `verified_by`. These columns make it unambiguous: `area_id` reflects where the project physically happened, with an evidence trail.

Setting `area_id` for SEO distribution would:
1. Pollute location data with strategic decisions
2. Make `location_confidence`, `location_status` meaningless for those projects  
3. Create false geographic claims visible in `hasVerifiedLocation()` logic (see `PublicPageController:364`)

**The correct approach**: a separate relationship for "area SEO support" — see `AREA_PROJECT_SUPPORT_ARCHITECTURE_V1.md`.

---

**لا Commit · لا Push — في انتظار موافقتك.**
