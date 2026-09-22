# 10 — PAGE PRODUCTION PRIORITY
**Vibe Clean Pro | الرياض | سبتمبر 2026**

> الترتيب: بناءً على (Intent التجاري × فجوة المحتوى × البنية التحتية الجاهزة)
> لا يُنفَّذ شيء بدون موافقة المالك على المحتوى المطلوب.

---

## الأولوية الصفرية (P0) — تعليق فوري

قبل أي إنتاج جديد، هذه القرارات يجب اتخاذها:

| المهمة | المسؤول | الأثر إذا تُرك |
|---|---|---|
| مراجعة 47 مشروع مع المالك: تأكيد الموقع + الإذن | المالك | صفحات مشاريع منشورة بدون إذن |
| قرار public_prices: هل نعرض أسعارًا؟ | المالك | لا يمكن استكمال صفحات الأسعار |
| noindex للمشاريع UNCONFIRMED | المطور | فهرسة Google لصفحات غير مكتملة |

---

## الدرجة الأولى — خدمات P1 (ترتيب V2 المعتمد)

> **⚠️ تحديث V2 — سبتمبر 2026:**
> الترتيب التالي صادر من OWNER DECISIONS V2 (القسم 41) ويلغي أي ترتيب سابق.
> الصفحات الذهبية (Facade, Post Construction, Glass, Exterior Surface) مُستثناة — لا تُعاد كتابتها.

### 1. إنتاج `/services/office-cleaning` — **أول صفحة تُنتَج**
**الأثر:** B2B عالي القيمة — المكاتب والشركات في الرياض
**البنية الجاهزة:** ✅ صفحة موجودة تحتاج تعميقًا
**المرجع:** `SERVICE_PAGE_BLUEPRINTS/office-cleaning-BLUEPRINT.md`

---

### 2. إنتاج Commercial Cleaning (مكاتب تجارية + محلات)
**يشمل:** shop-cleaning + منشآت تجارية + قسم B2B مكثف
**البنية الجاهزة:** ✅ shop-cleaning موجودة

---

### 3. تعميق `/services/villa-cleaning`
**الأثر:** صفحة قوية — تحتاج Decision Table للأنواع
**المرجع:** blueprint عند الإنشاء

---

### 4. إعادة بناء `/services/home-cleaning`
**الأثر:** صفحة شبه فارغة — معلومات المالك V2 تغطي كل ما نحتاجه
**البنية الجاهزة:** ✅ Route موجود، محتوى يحتاج بناء كاملًا
**المرجع:** `SERVICE_PAGE_BLUEPRINTS/home-cleaning-BLUEPRINT.md`

---

### 5. تعميق `/services/apartment-cleaning` (+ move-in intent)
**Intent مستقل:** «تنظيف وتجهيز المنازل قبل السكن» مُعتمد من V2 كـ intent مختلف.

---

### 6-8. تعميق: sofa / majlis / carpet (هيكل قائم)

---

### 9. إنشاء `/services/floor-cleaning`
**Intent مختلف:** «تنظيف أرضيات» ≠ «جلي رخام» — مُعتمد من V2

---

### 10-17. باقي الخدمات بالترتيب المعتمد من V2:
marble-polishing → kitchen-cleaning → bathroom-cleaning → water-tank → ac → pool → cleaning-contracts → disinfection

---

## الدرجة الثانية — تعميق خدمات قائمة (P1)

| الصفحة | الإضافة المطلوبة | جاهزية المحتوى |
|---|---|---|
| `villa-cleaning` | Decision Table: أنواع الفلل (صغيرة/كبيرة/مسبح) | يحتاج مالك |
| `apartment-cleaning` | قسم move-in H2 | يحتاج مالك |
| `sofa-cleaning` | مواد مختلفة (قماش/جلد/مخمل) | يحتاج مالك |
| `majlis-cleaning` | أنواع المجالس + قرار الحجم | يحتاج مالك |
| `office-cleaning` | B2B track: عقد دوري vs زيارة واحدة | يحتاج مالك |
| `carpet-cleaning` | سجاد vs موكيت vs مقاطف | يحتاج مالك |

---

## الدرجة الثالثة — خدمات جديدة (P2)

| الخدمة | القرار | الشرط |
|---|---|---|
| `/services/bathroom-cleaning` | صفحة مستقلة | 22 صورة حمامات + تأكيد نطاق الخدمة |
| `/services/move-in-cleaning` | قسم في apartment أولًا، صفحة لاحقًا | قرار المالك على نطاق الخدمة |
| `/services/facility-management` | قسم في cleaning-contracts | الإضافة للقيمة المؤسسية |

---

## الدرجة الرابعة — الأحياء (P2)

### 9 أحياء Tier-A — تعميق المحتوى

ترتيب التعميق (بحسب التجاري):

```
1. al-olaya (العليا) — B2B أعلى — office-cleaning + facade-cleaning
2. al-malqa (الملقا) — فلل راقية — villa-cleaning + facade-cleaning
3. al-yasmin (الياسمين) — سكني عالي — villa + majlis
4. al-narjis (النرجس) — أحياء جديدة — post-construction
5. al-qirawan (القيروان) — كثافة عالية — home + sofa
6. al-aqiq (العقيق) — تجاري/سكني — office + villa
7. al-yarmouk (اليرموك) — شرق — home + carpet
8. laban (لبن) — جنوب — home + sofa
9. diriyah (الدرعية) — تاريخي — facility + office
```

**الشرط الإلزامي قبل التعميق:** مشروع مؤكد في الحي (owner_confirmed_at)
**ما يُضاف بدون مشروع:** FAQ محلي + تعزيز معرفة الحي فقط

---

## الدرجة الخامسة — المقالات (P2)

### 5 مقالات ذات أولوية (AEO Clusters)

```
1. «متى تحتاج الواجهة إلى تنظيف؟» (Cluster: Facade)
   Intent: معلوماتي → تجاري
   Target URL: blog/متى-تنظيف-الواجهة

2. «الفرق بين التنظيف العادي والعميق» (Cluster: Home)
   Intent: معلوماتي
   Target URL: blog/تنظيف-عادي-أم-عميق

3. «كيف تختار شركة تنظيف في الرياض» (Cluster: Trust)
   Intent: معلوماتي → مقارنة
   Target URL: blog/اختيار-شركة-تنظيف

4. «أخطاء تنظيف الأرضيات بعد البناء» (Cluster: Post-Construction)
   Intent: معلوماتي
   Target URL: blog/أخطاء-تنظيف-بعد-البناء

5. «نصائح قبل وصول فريق التنظيف» (Cluster: Home/Trust)
   Intent: معلوماتي + تحضير للعميل
   Target URL: blog/تحضير-قبل-التنظيف
```

---

## الدرجة السادسة — Schema التقني (P2)

```
1. Person Schema للمؤسس (about page)
2. HowTo Schema للخدمات ذات Steps
3. ItemList لفهارس الخدمات
4. LocalBusiness areaServed (Tier-A فقط)
```

---

## ما لا يُنفَّذ الآن

```
❌ صفحات أحياء Tier-C (محجوبة بالنظام)
❌ صفحة لكل خدمة × حي (1260 صفحة — ممنوع استراتيجيًا)
❌ صور stock أو AI لأي صفحة
❌ تسعير على الصفحات قبل قرار public_prices
❌ مشاريع جديدة قبل تأكيد المالك على المشاريع الموجودة
```

---

## معيار «جاهز للإنتاج» (Ready for Production)

```
خدمة جديدة جاهزة للإنتاج عندما:
✅ قرار Architecture محسوم (صفحة مستقلة vs قسم)
✅ المالك أجاب على الأسئلة في 11_BUSINESS_INPUT_REQUIREMENTS.md
✅ موافقة على المحتوى المقترح
✅ صورة Cover متاحة من D:\VibeCleanPro_Media.zip

حي جاهز للتعميق عندما:
✅ مشروع مؤكد في الحي (owner_confirmed_at + area_id)
✅ أو المالك يوفر معرفة محلية خاصة بالحي
```
