# 02 — SEARCH INTENT & CANNIBALIZATION MAP
**Vibe Clean Pro | الرياض | سبتمبر 2026**

> القاعدة: لكل استعلام رئيسي URL أساسي واحد فقط. هذا الملف يُعيَّن فيه URL المالك لكل نية.
> **لا بيانات حجم بحث حقيقية متاحة. لا تُخترع أرقام.** — الترتيب مبني على منطق SERP والأهمية التجارية.

---

## قسم 1 — خريطة INTENT الكاملة للخدمات

### الخدمات التجارية / B2B (نية عالية)

| الاستعلام الرئيسي | النية | URL المالك | الصراع المحتمل |
|---|---|---|---|
| تنظيف واجهات بالرياض | تجارية/محلية | `/services/facade-cleaning` | ✅ لا صراع |
| تنظيف واجهات زجاج بالرياض | تجارية | `/services/facade-cleaning` ⚠️ | `/services/glass-cleaning` — يجب تمييز H1 |
| تنظيف زجاج داخلي بالرياض | تجارية/خدمة | `/services/glass-cleaning` | ✅ لا صراع |
| شركة تنظيف مكاتب بالرياض | تجارية/B2B | `/services/office-cleaning` | ✅ لا صراع |
| تنظيف شركات بالرياض | تجارية/B2B | `/services/office-cleaning` | ✅ |
| تنظيف محلات تجارية بالرياض | تجارية | `/services/shop-cleaning` | ✅ |
| شركة تنظيف منشآت بالرياض | تجارية/B2B | `/services/shop-cleaning` أو `/services/cleaning-contracts` | 🔴 **CANNIBALIZATION RISK** |
| عقود نظافة بالرياض | تجارية/B2B | `/services/cleaning-contracts` | ✅ |
| شركة نظافة للمنشآت | تجارية/B2B | `/services/cleaning-contracts` | 🟡 overlap مع shop |
| تنظيف ما بعد التشطيب بالرياض | تجارية | `/services/post-construction-cleaning` | ✅ |
| تنظيف بعد البناء | تجارية | `/services/post-construction-cleaning` | ✅ |

---

### الخدمات السكنية (نية بحث عالية)

| الاستعلام | النية | URL المالك | ملاحظة |
|---|---|---|---|
| تنظيف فلل بالرياض | تجارية/محلية | `/services/villa-cleaning` | ✅ |
| تنظيف منازل بالرياض | تجارية | `/services/home-cleaning` | ✅ — يحتاج تعميق عاجل |
| تنظيف شقق بالرياض | تجارية | `/services/apartment-cleaning` | ✅ |
| تنظيف كنب بالرياض | تجارية | `/services/sofa-cleaning` | ✅ |
| تنظيف مجالس بالرياض | تجارية | `/services/majlis-cleaning` | ✅ |
| تنظيف سجاد بالرياض | تجارية | `/services/carpet-cleaning` | ✅ |
| تنظيف موكيت بالرياض | تجارية | `/services/carpet-cleaning` | ✅ |
| جلي رخام بالرياض | تجارية | `/services/marble-polishing` | ✅ |
| تلميع أرضيات بالرياض | تجارية | `/services/marble-polishing` | 🟡 overlap مع floor-cleaning المفقودة |
| تنظيف مكيفات بالرياض | تجارية | `/services/ac-cleaning` | ✅ |
| تنظيف خزانات مياه بالرياض | تجارية | `/services/water-tank-cleaning` | ✅ |
| تنظيف مسابح بالرياض | تجارية | `/services/pool-cleaning` | ✅ |
| تعقيم منازل بالرياض | تجارية | `/services/disinfection` | ✅ |
| مكافحة حشرات بالرياض | تجارية | `/services/pest-control` | ✅ |

---

### الاستعلامات ذات الـ CANNIBALIZATION RISK

| الاستعلام | المشكلة | القرار |
|---|---|---|
| تنظيف واجهات زجاج | facade-cleaning + glass-cleaning يتنافسان | **تدقيق H1**: facade: «زجاج مرتفع وواجهات خارجية»، glass: «زجاج داخلي وقواطع ومحلات» |
| تنظيف منشآت / تنظيف مكاتب | office-cleaning + shop-cleaning + cleaning-contracts | فصل Intent: مكاتب=office, محلات=shop, عقود=contracts |
| جلي رخام / تنظيف أرضيات | marble-polishing (موجود) vs floor-cleaning (مفقود) | **قرار مطلوب**: هل floor-cleaning صفحة مستقلة؟ البيانات تقول نعم (intent مختلف) |
| تنظيف منازل / تنظيف فلل / تنظيف شقق | 3 صفحات تتنافس على نفس الفئة | ✅ **Intent مختلف**: villa=فلل كبيرة, home=منازل عائلية, apartment=شقق/أبراج — **احتفظ بالثلاثة** مع تمييز H1 واضح |
| تنظيف المنزل الجديد قبل السكن | post-construction-cleaning + لا صفحة مستقلة | **قرار**: هل هي Intent مستقلة؟ نعم — «تنظيف شقق ومنازل التسليم والانتقال الجديد» يختلف عن post-construction |

---

### الاستعلامات بدون صفحة مالك

| الاستعلام | Intent | القرار المقترح |
|---|---|---|
| تنظيف مطابخ بالرياض | تجارية (محلات + سكني) | **صفحة مستقلة** `/services/kitchen-cleaning` — دليل موجود (12 صورة مطابخ) |
| تنظيف حمامات بالرياض | تجارية | **صفحة مستقلة** `/services/bathroom-cleaning` — دليل موجود (22 صورة حمامات) |
| تنظيف أرضيات بالرياض | تجارية | **صفحة مستقلة** `/services/floor-cleaning` — Intent مختلف عن marble-polishing |
| تنظيف المنازل الجديدة قبل السكن | تجارية/خاص | **صفحة مستقلة** `/services/move-in-cleaning` أو قسم في apartment/home |
| إدارة مرافق بالرياض | B2B/contracts | مرتبط بـcleaning-contracts — **قرار: قسم أم صفحة؟** |

---

## قسم 2 — Intent Map التفصيلي لكل خدمة رئيسية

### تنظيف واجهات المباني

```
Primary Intent:    تجارية/محلية — إيجاد مقاول لتنظيف واجهة مبنى في الرياض
Secondary Intent:  مقارنة شركات + معرفة التكلفة
Commercial Intent: 🔴 عالي جدًا
Local Intent:      🔴 الرياض إلزامي
Problem Intent:    «واجهتي متسخة وارتفاعها يمنع التنظيف العادي»
Price Intent:      «كم يكلف تنظيف واجهة مبنى؟»
Comparison Intent: «حبال أم رافعة؟»
Trust Intent:      «هل آمن؟ هل يحتاج تصريح؟»

Primary Query:     تنظيف واجهات بالرياض
Secondary Queries: تنظيف واجهات زجاج / حجر / كلادينج / أبراج / بالحبال / الرافعة
Entities:          زجاج، حجر، كلادينج، ألمنيوم، رافعة، حبال، سقالة، مبنى، برج، غبار
Decision Factors:  الارتفاع، طريقة الوصول، نوع المادة، الأمان، المدة
```

### تنظيف ما بعد البناء

```
Primary Intent:    تجارية — تنظيف شامل بعد انتهاء التشطيب
Problem Intent:    «غبار البناء + بقايا اسمنت + دهان + زجاج = فوضى»
Trust Intent:      «هل ستتلفون الأرضيات؟»
Sequence Intent:   «متى بالضبط أطلب التنظيف — قبل أم بعد نقل الأثاث؟»
Primary Query:     تنظيف ما بعد التشطيب بالرياض
Entities:          غبار بناء، اسمنت، دهان، رخام، زجاج، أرضيات، نقل أثاث
```

### تنظيف المنازل (home-cleaning)

```
Primary Intent:    تجارية/سكنية — خدمة منتظمة أو عميقة للمنزل
Problem Intent:    «التنظيف الروتيني لا يكفي» / «ليس لدي وقت»
Comparison Intent: «التنظيف الدوري مقابل التنظيف العميق»
Trust Intent:      «من سيدخل بيتي؟ هل آمن؟»
Primary Query:     تنظيف منازل بالرياض
Entities:          غرف، مطبخ، حمام، أثاث، ستائر، نوافذ
Decision Factors:  التكرار، العمق، السعر، الأمانة، الأمان
```

---

## قسم 3 — قرارات Cannibalization

### مؤكدة — لا تعديل

| الاستعلام | الحل المعتمد |
|---|---|
| تنظيف منازل vs فلل vs شقق | **3 صفحات مستقلة** — Intent واضح ومختلف |
| facade-cleaning vs glass-cleaning | **صفحتان** — تم الفصل بقرار موثق في competitor-gap-analysis |
| courtyard-cleaning vs exterior-surface | **تُبقى courtyard-cleaning** — القرار الموثق في README |

### تحتاج حسم

| المشكلة | الخيارات |
|---|---|
| shop-cleaning vs cleaning-contracts للمنشآت | A) shop=تجزئة/محلات + contracts=عقود دورية ← **المفضّل** | B) دمج |
| marble-polishing vs floor-cleaning | A) مستقلان (intent مختلف: جلي=معالجة الرخام، floor=تنظيف الأرضيات عمومًا) ← **المفضّل** | B) floor = قسم في marble |
| home-cleaning vs move-in-cleaning | A) move-in = قسم في home ← **مؤقتًا** | B) صفحة مستقلة لاحقًا إذا ثبت Intent |
