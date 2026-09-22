# 08 — STRUCTURED DATA AUDIT
**Vibe Clean Pro | الرياض | سبتمبر 2026**

---

## الحالة الراهنة — ما هو منفَّذ

بناءً على `app/Seo/StructuredDataGenerator.php`:

| نوع Schema | الصفحات | الحالة |
|---|---|---|
| `WebPage` | كل الصفحات | ✅ قاعدي |
| `LocalBusiness` | كل الصفحات | ✅ من Business Profile |
| `Service` | صفحات الخدمات | ✅ |
| `FAQPage` | Areas, Articles, Projects, Offers | ✅ (Service excluded بقرار) |
| `Article` | صفحات المقالات | ✅ |
| `BreadcrumbList` | كل الصفحات ذات breadcrumb | ✅ |
| `ImageObject` | صور المشاريع | 🔴 غير مؤكد |
| `Organization` | / | 🟡 مُدمج في LocalBusiness؟ |
| `Person` | about | 🔴 غائب |
| `HowTo` | خدمات بـ Steps | 🟡 يحتاج فحص |
| `ItemList` | فهارس الخدمات/الأحياء | 🔴 غائب |
| `Review` / `AggregateRating` | لا شيء | ✅ صواب (لا تقييمات حقيقية) |

---

## القرار الاستراتيجي: FAQPage Schema

### الحالة

Google في 2023 قلّصت ظهور FAQPage rich results لتقتصر فعليًا على:
- المواقع الحكومية والصحية الكبرى
- مواقع محددة بعينها (Google's discretion)

### القرار المُعتمد لـ Vibe Clean Pro

```
Service pages:   ❌ بدون FAQPage (قرار مُطبَّق في الكود)
Area pages:      🟡 مسموح — المحتوى المحلي يُضيف قيمة للـ Indexing حتى بدون Rich Result
Article pages:   🟡 مسموح — المقالات الطويلة تستفيد من الـ Schema للـ Crawling
Project pages:   🟡 مسموح — أسئلة خاصة بالمشروع
Offer pages:     🟡 مسموح — أسئلة خاصة بالعرض
Trust/About:     ❌ بدون FAQPage — غير مناسب للصفحات القانونية
```

**الأساس:** Schema.org و Google بما يُشير إلى أن FAQPage لا يزال مُفسَّرًا حتى بدون Rich Result — يساعد في الـ Entity Understanding. القرار الحالي متوازن.

---

## Schema المفقودة — الأولوية

### P0 — Person Schema للمؤسس

```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "[اسم المؤسس]",
  "jobTitle": "المؤسس والرئيس التنفيذي",
  "worksFor": {
    "@type": "LocalBusiness",
    "name": "Vibe Clean Pro"
  },
  "url": "https://vibecleanpro.sa/about",
  "description": "[Bio من قاعدة البيانات]"
}
```

**لماذا هو P0:** يُقوّي E-E-A-T للمقالات والمحتوى. الـ Business Profile مكتمل في قاعدة البيانات لكن غير مُخرَج كـ Schema.

**الملف المطلوب تعديله:** `app/Seo/StructuredDataGenerator.php` → إضافة `forAboutPage()`

---

### P1 — HowTo Schema للخدمات ذات Steps

```json
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "كيفية تنظيف واجهة المبنى",
  "description": "...",
  "step": [
    {
      "@type": "HowToStep",
      "name": "تقييم الواجهة",
      "text": "..."
    }
  ]
}
```

**الصفحات المستهدفة:** facade-cleaning, post-construction-cleaning, water-tank-cleaning, ac-cleaning
**الشرط:** الصفحة لديها content blocks من نوع `steps` بعدد ≥ 3

**الملف المطلوب:** `StructuredDataGenerator::forServicePage()` → فحص steps blocks → توليد HowTo

---

### P1 — ItemList لفهرس الخدمات والأحياء

```json
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "خدمات التنظيف في الرياض",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "تنظيف الواجهات",
      "url": "https://vibecleanpro.sa/services/facade-cleaning"
    }
  ]
}
```

**الصفحات:** /services (فهرس), /areas (فهرس), / (الرئيسية — قسم الخدمات)

---

### P2 — ImageObject لصور المشاريع

```json
{
  "@context": "https://schema.org",
  "@type": "ImageObject",
  "contentUrl": "...",
  "description": "[وصف الصورة من alt]",
  "creator": {
    "@type": "Organization",
    "name": "Vibe Clean Pro"
  }
}
```

**الشرط:** الصورة منشورة (public) وفي مشروع مؤكد (owner_confirmed_at NOT NULL)
**لماذا:** يُساعد في Google Discover وGoogle Images للمحتوى البصري المحلي.

---

### P2 — LocalBusiness Enhancement

الحالة: LocalBusiness Schema موجود، لكن يحتاج تعزيزًا:

```json
{
  "@type": ["LocalBusiness", "HouseholdService"],
  "name": "Vibe Clean Pro",
  "areaServed": [
    {"@type": "City", "name": "الرياض"},
    {"@type": "Neighborhood", "name": "الملقا"},
    ...9 Tier-A areas
  ],
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "خدمات التنظيف",
    "itemListElement": [...]
  }
}
```

**الشرط:** تُضاف `areaServed` من قائمة Tier-A فقط (9 أحياء) + مدينة الرياض.

---

### P3 — Article AuthorEntity Markup

لكل مقال، يجب أن يُشير الـ `author` في Article Schema إلى Person Schema المؤسس:

```json
{
  "@type": "Article",
  "author": {
    "@type": "Person",
    "name": "[اسم المؤسس]",
    "url": "https://vibecleanpro.sa/about"
  }
}
```

**الشرط:** ربط المقالات بالمؤسس في قاعدة البيانات (حقل author/user_id)

---

## Schema التي يجب إزالتها أو عدم إضافتها

| Schema | السبب |
|---|---|
| `Review` / `AggregateRating` | لا تقييمات حقيقية موثقة — ممنوع اختراع بيانات |
| `PriceRange` في LocalBusiness | public_prices table غير موجود — يُضاف فقط بعد قرار التسعير |
| `Product` | المنتج هو الخدمة — Service Schema أنسب |
| `Event` | لا فعاليات |
| `JobPosting` | لا وظائف شاغرة |

---

## خطة التنفيذ

```
المرحلة 1 — عاجل (P0):
  1. إضافة Person Schema في forAboutPage()
  2. تعزيز LocalBusiness بـ areaServed (Tier-A فقط)

المرحلة 2 — قريب (P1):
  3. إضافة HowTo Schema للخدمات ذات Steps
  4. إضافة ItemList لفهارس الخدمات والأحياء

المرحلة 3 — متوسط الأمد (P2):
  5. ImageObject للمشاريع المؤكدة
  6. Article author → Person Entity

المرحلة 4 — بعد التسعير (P3):
  7. PriceRange في LocalBusiness بعد قرار public_prices
```

---

## ملاحظة اختبار Schema

بعد أي تعديل في StructuredDataGenerator:
```bash
php artisan test --filter=StructuredData
php artisan test tests/Feature/ServiceDetailTest.php
php artisan test tests/Feature/AboutPageTest.php
```

يمكن التحقق عبر: Google Rich Results Test أو Schema.org Validator
