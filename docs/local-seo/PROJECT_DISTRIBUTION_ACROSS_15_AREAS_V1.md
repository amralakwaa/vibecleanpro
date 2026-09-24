# Project Distribution Across 15 Riyadh Areas — V1 (Corrected)

**Date:** 2026-09-24  
**Status:** CORRECTED — area_id column reverted to NULL for all 47 projects (no confirmed locations)  
**Rule:** `ACTUAL LOCATION` = confirmed by DB evidence only. `AREA SEO SUPPORT` = strategic assignment pending new `area_project_support` mechanism.

---

## Key Distinction

| Column | Meaning | Source |
|--------|---------|--------|
| ACTUAL LOCATION | Physical location confirmed in DB (`location_status`, `verified_at`) | `projects.area_id` (location field only) |
| AREA SEO SUPPORT | "This project's content supports this area page" | `area_project_support` pivot (pending migration) |

---

## All 47 Projects — Distribution Table

| ID | Title | ACTUAL LOCATION | AREA SEO SUPPORT (Primary) | AREA SEO SUPPORT (Secondary) | Has B/A Media? |
|----|-------|----------------|---------------------------|------------------------------|----------------|
| P1 | تنظيف شامل بعد التشطيب — وحدة سكنية | ❌ NONE | العارض | القيروان | ❌ |
| P2 | تنظيف واجهة زجاجية لمبنى تجاري | ❌ NONE | المونسية | العليا | ❌ |
| P3 | تنظيف شامل لفيلا سكنية — أعمال كاملة | ❌ NONE | الملقا | الياسمين, الربيع | ❌ |
| P4 | تنظيف أحواش ومداخل فيلا — إنترلوك وأرضيات | ❌ NONE | الربيع | الملقا | ❌ |
| P5 | تنظيف أحواش وممرات خارجية — قبل الاستخدام | ❌ NONE | الربيع | العارض | ❌ |
| P6 | تنظيف بعد البناء لمشروع سكني — أرضيات ومرافق | ❌ NONE | العارض | القيروان | ❌ |
| P7 | تنظيف حمامات وأرضيات — منزل وبيئة عمل | ❌ NONE | الياسمين | الغدير | ❌ |
| P8 | تنظيف واجهة مبنى — طوابق متعددة | ❌ NONE | المونسية | العليا | ❌ |
| P9 | تنظيف مكاتب إدارية — بيئة عمل احترافية | ❌ NONE | التعاون | العليا | ❌ |
| P10 | تنظيف واجهة زجاجية لمبنى — محيط تجاري | ❌ NONE | المونسية | الصحافة | ❌ |
| P11 | تنظيف زجاج الواجهات الداخلية والخارجية | ❌ NONE | الصحافة | المونسية | ❌ |
| P12 | تنظيف مكاتب وصالة استقبال | ❌ NONE | التعاون | العليا | ❌ |
| P13 | تنظيف أحواش وممرات فيلا — بلاط وجرانيت | ❌ NONE | الربيع | الملقا | ❌ |
| P14 | تنظيف حوش ومدخل فيلا | ❌ NONE | الربيع | الياسمين | ❌ |
| P15 | تنظيف دورات مياه في منشأة خدمية | ❌ NONE | التعاون | المونسية | ❌ |
| P16 | تنظيف فيلا سكنية — داخلي شامل | ❌ NONE | الياسمين | الملقا | ❌ |
| P17 | تنظيف مكاتب إدارية — طابق كامل | ❌ NONE | التعاون | العليا | ❌ |
| P18 | تنظيف الارتفاعات والأسقف الداخلية | ❌ NONE | حطين | النخيل | ❌ |
| P19 | تنظيف زجاج خارجي لمبنى تجاري | ❌ NONE | المونسية | التعاون | ❌ |
| P20 | تنظيف وجلي أرضيات رخام — فيلا | ❌ NONE | الملقا | النخيل | ❌ |
| P21 | تنظيف ممرات وأرضيات مبنى إداري | ❌ NONE | التعاون | العليا | ❌ |
| P22 | تنظيف بعد التشطيب — مشروع سكني صغير | ❌ NONE | القيروان | العارض | ❌ |
| P23 | تنظيف مكتب — فضاء مفتوح | ❌ NONE | التعاون | العليا | ❌ |
| P24 | تنظيف مواقف السيارات في منشأة تجارية | ❌ NONE | التعاون | المونسية | ❌ |
| P25 | تنظيف مجمع تجاري ومحلات | ❌ NONE | التعاون | العليا | ❌ |
| P26 | تنظيف واجهة مبنى سكني | ❌ NONE | الصحافة | النرجس | ❌ |
| P27 | تنظيف مرافق منشأة تجارية — ممرات مشتركة | ❌ NONE | التعاون | المونسية | ❌ |
| P28 | تنظيف زجاج خارجي لمبنى | ❌ NONE | الصحافة | المونسية | ❌ |
| P29 | تنظيف مطبخ شامل — منزل سكني | ❌ NONE | الياسمين | الربيع | ❌ |
| P30 | تنظيف حمامات في وحدة سكنية | ❌ NONE | الصحافة | الياسمين | ❌ |
| P31 | تنظيف واجهة مبنى — خارجي أ | ❌ NONE | المونسية | الرمال | ❌ |
| P32 | تنظيف واجهة زجاجية زرقاء — مبنى تجاري | ❌ NONE | المونسية | التعاون | ❌ |
| P33 | تنظيف الثريات والإضاءة الداخلية — فيلا | ❌ NONE | حطين | الملقا | ❌ |
| P34 | تنظيف مكاتب — بيئة عمل | ❌ NONE | التعاون | العليا | ❌ |
| P35 | جلي وتلميع رخام — أرضيات وأسطح | ❌ NONE | الملقا | النخيل | ❌ |
| P36 | تنظيف واجهة مبنى — خارجي ب | ❌ NONE | المونسية | الرمال | ❌ |
| P37 | تنظيف مكتب — ما بعد التشطيب | ❌ NONE | التعاون | العارض | ❌ |
| P38 | تنظيف منزل شامل مع التسليم | ❌ NONE | الياسمين | الربيع | ❌ |
| P39 | تنظيف بعد البناء — وحدة سكنية | ❌ NONE | القيروان | العارض | ❌ |
| P40 | تنظيف فيلا | ❌ NONE | النرجس | الياسمين | ❌ |
| P41 | تنظيف عام | ❌ NONE | الغدير | التعاون | ❌ |
| P42 | تنظيف حوش ومداخل فيلا | ❌ NONE | الربيع | العارض | ❌ |
| P43 | تنظيف واجهة مبنى — خارجي ج | ❌ NONE | المونسية | الصحافة | ❌ |
| P44 | تنظيف وتلميع أرضيات | ❌ NONE | الملقا | الياسمين | ❌ |
| P45 | تنظيف دورات مياه منشأة | ❌ NONE | التعاون | المونسية | ❌ |
| P46 | تنظيف مكاتب — مبنى إداري | ❌ NONE | التعاون | العليا | ❌ |
| P47 | تنظيف فيلا سكنية | ❌ NONE | النرجس | الغدير | ❌ |

---

## Coverage per Area

| Area | SEO Support Projects (Primary) | Count |
|------|-------------------------------|-------|
| التعاون | P9, P12, P15, P17, P19(sec), P21, P23, P24, P25, P27, P32(sec), P34, P37, P41(sec), P45, P46 | 13 primary |
| المونسية | P2, P8, P10, P19, P24(sec), P27(sec), P31, P32, P36, P43, P45(sec), P15(sec) | 8 primary |
| الملقا | P3, P4(sec), P13(sec), P20, P33(sec), P35, P44 | 5 primary |
| الربيع | P4, P5, P13, P14, P29(sec), P38(sec), P42 | 5 primary |
| العارض | P1, P5(sec), P6, P22(sec), P37(sec), P39(sec), P42(sec) | 3 primary |
| الياسمين | P3(sec), P7, P14(sec), P16, P29, P38, P40(sec), P44(sec) | 5 primary |
| الصحافة | P10(sec), P11, P26, P28, P30, P43(sec) | 4 primary |
| حطين | P18, P33 | 2 primary |
| القيروان | P1(sec), P6(sec), P22, P39 | 2 primary |
| الغدير | P7(sec), P41, P47(sec) | 1 primary |
| النرجس | P26(sec), P40, P47 | 2 primary |
| الرمال | P31(sec), P36(sec) | 0 primary (supporting only) |
| العليا | P9(sec), P12(sec), P17(sec), P21(sec), P23(sec), P25(sec), P34(sec), P46(sec) | 0 primary (supporting only) |
| النخيل | P18(sec), P20(sec), P35(sec) | 0 primary (supporting only) |
| المحمدية | P41(supporting) | 0 primary (draft — low priority) |

---

## Areas With Zero Primary Projects

**الرمال, العليا, النخيل, المحمدية** have only secondary/supporting projects. This is acceptable because:
- الرمال and النخيل are recommended noindex during rebuild
- العليا has strong existing content and service pages
- المحمدية is a draft area

---

## Notes

1. All `ACTUAL LOCATION` entries are `NONE` — no project has a confirmed location in the DB
2. `AREA SEO SUPPORT` assignments are **pending** the `area_project_support` migration approval
3. Once migration exists, these assignments will be seeded via `area_project_support` pivot
4. Display label: "من أعمال فايب كلين برو في الرياض" — city-wide, no neighborhood claim

---

**لا Commit · لا Push — في انتظار موافقتك.**
