# 06 — MASTER INTERNAL LINK GRAPH
**Vibe Clean Pro | الرياض | سبتمبر 2026**

> القاعدة: كل رابط داخلي له غرض (نقل PageRank + إرشاد المستخدم). لا روابط عشوائية.

---

## المحور الأول — الصفحة الرئيسية كـ Hub

### الروابط الصادرة من الرئيسية

```
HOME (/)
 ├──► الخدمات الأعلى نية تجارية (6 روابط بارزة في Hero/Features):
 │     facade-cleaning, villa-cleaning, post-construction-cleaning,
 │     office-cleaning, cleaning-contracts, home-cleaning
 │
 ├──► أحياء الأولوية A (3-4 روابط في قسم "المناطق"):
 │     al-malqa, al-qirawan, al-yasmin, al-olaya
 │
 ├──► أحدث المشاريع (3 روابط بعد التأكيد):
 │     [ينتظر owner_confirmed_at]
 │
 ├──► أحدث المقالات (3 روابط):
 │     blog/[latest 3]
 │
 └──► Trust Pages:
       /about, /warranty
```

---

## المحور الثاني — Service Cluster Linking

### مبدأ Service Cluster

كل خدمة ترتبط بـ:
1. خدمات تكميلية (2-4 روابط)
2. مقال داعم (1-2 روابط)
3. عرض ذو صلة (1 رابط إن وجد)
4. حي نموذجي (1 رابط)

### خريطة الروابط التكميلية

```
facade-cleaning
  → glass-cleaning (الزجاج الداخلي)
  → post-construction-cleaning (بعد تشطيب الواجهة)
  → office-cleaning (للمباني التجارية)
  → marble-polishing (أرضيات الردهات)

glass-cleaning
  → facade-cleaning (الواجهات الخارجية)
  → post-construction-cleaning
  → office-cleaning

post-construction-cleaning
  → facade-cleaning (الواجهة بعد البناء)
  → marble-polishing (الأرضيات)
  → home-cleaning (ما بعد السكن)
  → villa-cleaning

villa-cleaning
  → home-cleaning (للمنازل الصغيرة)
  → majlis-cleaning (المجلس داخل الفيلا)
  → sofa-cleaning
  → carpet-cleaning

home-cleaning
  → villa-cleaning (للفلل الكبيرة)
  → apartment-cleaning (للشقق)
  → sofa-cleaning
  → carpet-cleaning

apartment-cleaning
  → home-cleaning
  → post-construction-cleaning (move-in)
  → sofa-cleaning

office-cleaning
  → facade-cleaning
  → cleaning-contracts (العقد الدوري)
  → shop-cleaning

shop-cleaning
  → office-cleaning
  → cleaning-contracts
  → facade-cleaning (واجهة المحل)

cleaning-contracts
  → office-cleaning
  → shop-cleaning
  → facade-cleaning

sofa-cleaning
  → carpet-cleaning
  → majlis-cleaning
  → villa-cleaning

majlis-cleaning
  → sofa-cleaning
  → carpet-cleaning
  → home-cleaning

carpet-cleaning
  → sofa-cleaning
  → majlis-cleaning

marble-polishing
  → post-construction-cleaning
  → facade-cleaning
  → villa-cleaning

ac-cleaning
  → water-tank-cleaning
  → disinfection (تعقيم ما بعد التنظيف)

water-tank-cleaning
  → ac-cleaning
  → disinfection

pool-cleaning
  → disinfection
  → villa-cleaning

disinfection
  → pest-control
  → home-cleaning
  → ac-cleaning

pest-control
  → disinfection
  → home-cleaning
```

---

## المحور الثالث — Area ↔ Service Linking

### مبدأ صفحات الأحياء

كل صفحة حي تحتوي:
1. قسم «الخدمات المتاحة في [الحي]» بروابط مباشرة للخدمات المناسبة
2. رابط لأقرب حيين مجاورين (nearby areas)
3. رابط للرئيسية ضمن Breadcrumb
4. روابط للمشاريع الموجودة في الحي (بعد التأكيد)

### خريطة الخدمات حسب نوع الحي

```
أحياء الشمال الراقية (الملقا، الياسمين، النرجس، القيروان):
  الخدمات المُرتبطة: facade-cleaning, villa-cleaning,
                    post-construction-cleaning, marble-polishing,
                    majlis-cleaning, carpet-cleaning, sofa-cleaning

أحياء B2B/تجارية (العليا، العقيق):
  الخدمات: office-cleaning, facade-cleaning, cleaning-contracts,
            shop-cleaning, glass-cleaning

أحياء سكنية متوسطة (الروضة، الياسمين، المروج):
  الخدمات: home-cleaning, sofa-cleaning, carpet-cleaning,
            majlis-cleaning, ac-cleaning

أحياء شرق جديدة (غرناطة، إشبيلية، القدس):
  الخدمات: post-construction-cleaning, home-cleaning,
            apartment-cleaning, marble-polishing

أحياء شعبية/متنوعة (اليرموك، الحمراء):
  الخدمات: home-cleaning, sofa-cleaning, ac-cleaning,
            carpet-cleaning, water-tank-cleaning
```

---

## المحور الرابع — Article Cluster Linking

### مبدأ المقالات

كل مقال يرتبط بـ:
1. خدمة رئيسية ذات صلة (Pillar Link) — إلزامي
2. خدمة تكميلية (Spoke Link) — إذا وُجد
3. مقال آخر ضمن نفس الـ Cluster (Article-to-Article) — اختياري

### مثال: مقال «كيف نظّف واجهتك بعد عاصفة الرمال»
```
Pillar Link: /services/facade-cleaning
Spoke Link:  /services/glass-cleaning
Area Link:   /areas/al-olaya (إذا كانت المقالة بمثال من العليا)
```

### مثال: مقال «ما الفرق بين التنظيف العميق والدوري؟»
```
Pillar Link: /services/home-cleaning
Spoke Links: /services/villa-cleaning, /services/cleaning-contracts
```

---

## المحور الخامس — Project → Area + Service Linking

### الحالة الراهنة
```
جميع المشاريع area_id = NULL → روابط Area معطّلة
```

### الخريطة المطلوبة (بعد تأكيد المالك)

```
/projects/[project-slug]
  → /areas/[confirmed-area]          (حتى ترتبط المنطقة الجغرافية)
  → /services/[primary-service]      (الخدمة الرئيسية للمشروع)
  → /services/[secondary-service]    (إذا غطّى المشروع خدمة ثانية)
```

### ما يجب إضافته في صفحة الحي عند توافر المشاريع

```
/areas/[area-slug]
  → /projects/[project1]   (أول مشروع مؤكد في الحي)
  → /projects/[project2]   (ثاني مشروع مؤكد)
  → /services/[service]    (الخدمة الأبرز في الحي)
```

---

## المحور السادس — Offer ↔ Service Linking

```
كل عرض يرتبط بالخدمة أو الخدمات التي يغطيها
/offers/[offer-slug]
  → /services/[service1]
  → /services/[service2]  (إذا غطّى أكثر من خدمة)
  → /areas/[area]         (إذا العرض مقيّد بمنطقة معينة)
```

---

## المحور السابع — Trust/About → Linking Out

```
/about
  → /services/facade-cleaning    (أبرز خدمة)
  → /projects/[featured-project] (مشروع بارز بعد التأكيد)
  → /blog/[founder-article]      (مقال يكتبه المؤسس)

/warranty
  → /services/villa-cleaning     (الخدمة الأعلى ضمانًا)
  → /services/facade-cleaning
  → /about

/blog (فهرس المقالات)
  → كل خدمة مذكورة في المقالات
```

---

## المحور الثامن — Sitemap Priority & Crawl Budget

```
Priority 1.0: /  (الرئيسية)
Priority 0.9: /services/*  (19 صفحة)
Priority 0.8: /areas/[Tier-A]  (9 صفحات)
Priority 0.7: /projects/*  (47 صفحة — تُخفَّض حتى تأكيد owner_confirmed_at)
Priority 0.6: /areas/[Tier-B]  (75 صفحة)
Priority 0.6: /blog/*  (10 مقالات)
Priority 0.5: /offers/*  (7 صفحات)
Priority 0.4: /about, /warranty, /privacy, /terms
```

---

## قواعد Internal Linking

```
✅ يجب أن يكون كل رابط داخلي وصفيًا في anchor text
   ← «خدمة تنظيف الواجهات» وليس «اضغط هنا»

✅ كل صفحة خدمة: 3-5 روابط صادرة لخدمات أخرى
✅ كل صفحة حي: 3-6 روابط للخدمات + 2 أحياء مجاورة
✅ كل مقال: رابط واحد على الأقل لصفحة خدمة

❌ لا روابط بين أحياء غير مجاورة جغرافيًا
❌ لا روابط دورية (Service A → Service B → Service A فقط)
❌ لا روابط لمشاريع UNCONFIRMED حتى تأكيد المالك
❌ لا روابط لـ /areas/[Tier-C] — هذه غير منشورة
```

---

## فجوة عاجلة: صفحات يتيمة (Orphan Pages)

بناءً على البنية الحالية، هذه الصفحات قد تكون يتيمة:

```
مشاريع UNCONFIRMED (47) — لا روابط من الأحياء لأنها area_id = NULL
مقالات بدون ربط بخدمات (تحتاج فحص)
صفحات Tier-B الأحياء الأضعف — قد لا ترتبط بأي مشروع
/about — كم رابط يُشير إليها من الصفحات الأخرى؟
```
