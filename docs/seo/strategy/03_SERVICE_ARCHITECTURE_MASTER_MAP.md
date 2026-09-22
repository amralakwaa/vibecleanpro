# 03 — SERVICE ARCHITECTURE MASTER MAP
**Vibe Clean Pro | الرياض | سبتمبر 2026**

---

## الحالة الراهنة: 19 صفحة خدمة منشورة

```
/services/
├── home-cleaning          [🔴 يحتاج إعادة بناء — محتوى هزيل]
├── villa-cleaning         [✅ قوية — تعميق إضافي مطلوب]
├── apartment-cleaning     [🟡 هيكل أساسي جيد]
├── sofa-cleaning          [🟡 هيكل أساسي]
├── majlis-cleaning        [🟡 هيكل أساسي]
├── carpet-cleaning        [🟡 هيكل أساسي]
├── office-cleaning        [🟡 جيدة — تعميق B2B مطلوب]
├── shop-cleaning          [🟡 هيكل أساسي]
├── facade-cleaning        [✅ GOLD STANDARD]
├── post-construction-cleaning [✅ قوية]
├── glass-cleaning         [✅ معتمدة]
├── water-tank-cleaning    [🟡 هيكل أساسي]
├── ac-cleaning            [🟡 هيكل أساسي]
├── marble-polishing       [🟡 هيكل أساسي]
├── pool-cleaning          [🟡 مشروطة — يحتاج تأكيد قدرة تشغيلية]
├── disinfection           [🟡 هيكل أساسي — يحتاج تأكيد قدرة]
├── pest-control           [🟡 مشروطة — ترخيص؟]
├── cleaning-contracts     [🟡 هيكل أساسي — B2B عالية القيمة]
└── courtyard-cleaning     [🟡 هيكل أساسي]
```

---

## الخدمات المفقودة — قرار Architecture مطلوب

| الخدمة | الحالة | القرار المقترح | السبب |
|---|---|---|---|
| `/services/kitchen-cleaning` | MISSING | **صفحة مستقلة** | Intent تجاري واضح. 12 صورة مطابخ متاحة. المنافسون لديهم صفحات |
| `/services/bathroom-cleaning` | MISSING | **صفحة مستقلة** | Intent مختلف عن home-cleaning. 22 صورة حمامات متاحة |
| `/services/floor-cleaning` | MISSING | **صفحة مستقلة** | Intent مختلف عن marble-polishing. «تنظيف أرضيات» ≠ «جلي رخام» |
| `/services/move-in-cleaning` | MISSING | **قسم في apartment-cleaning** أو صفحة مستقلة لاحقًا | Intent قريب جدًا من apartment. يُنشأ كـ H2 أولًا ثم يُرقَّى |
| `/services/facility-management` | MISSING | **قسم في cleaning-contracts** | Intent متداخل. لا دليل مستقل |

---

## هيكل الخدمات المقترح (بعد الإكمال)

```
COMMERCIAL / B2B CLUSTER
  /services/facade-cleaning         ✅ منشورة
  /services/glass-cleaning          ✅ منشورة
  /services/post-construction-cleaning ✅ منشورة
  /services/office-cleaning         ✅ تعميق B2B
  /services/shop-cleaning           ✅ تعميق commercial
  /services/cleaning-contracts      ✅ يُضاف facility mgmt كقسم
  /services/courtyard-cleaning      ✅ منشورة

RESIDENTIAL CLUSTER
  /services/villa-cleaning          ✅ منشورة
  /services/home-cleaning           🔴 تحتاج إعادة بناء عاجلة
  /services/apartment-cleaning      🟡 تعميق + قسم move-in

SOFT FURNISHING CLUSTER
  /services/sofa-cleaning           🟡 تعميق
  /services/majlis-cleaning         🟡 تعميق
  /services/carpet-cleaning         🟡 تعميق

SURFACES & FLOORS CLUSTER
  /services/marble-polishing        🟡 تعميق
  /services/floor-cleaning          🔲 NEW — P1
  /services/kitchen-cleaning        🔲 NEW — P1
  /services/bathroom-cleaning       🔲 NEW — P2

SPECIALIZED / TECHNICAL CLUSTER
  /services/ac-cleaning             🟡 تعميق
  /services/water-tank-cleaning     🟡 تعميق
  /services/pool-cleaning           🟡 تأكيد قدرة
  /services/disinfection            🟡 تأكيد قدرة
  /services/pest-control            🟡 تأكيد ترخيص
```

---

## معيار قرار PAGE vs SECTION vs ARTICLE

| المعيار | PAGE | SECTION | ARTICLE |
|---|---|---|---|
| Intent مستقل في SERP | ✅ | — | — |
| حجم طلب تجاري كافٍ | ✅ | ✅ | — |
| دليل مستقل (صور/مشاريع) | ✅ | ⚠️ | ⚠️ |
| لا صفحة منافسة أوثق منا | ✅ | — | — |
| معلومة فقط / awareness | — | ⚠️ | ✅ |
| Keyword informational فقط | — | — | ✅ |

---

## Content Depth Requirements — بحسب المجموعة

### Commercial/B2B (facade, post-construction, office, contracts)
- Hero: صورة حقيقية تُظهر المعدات/الفريق/الموقع
- Direct Answer: تعريف + نطاق العمل
- أنواع / مواد (H2 منفصل لكل نوع رئيسي)
- طريقة التنفيذ مع تفاصيل تشغيلية حقيقية
- قسم تمييزي: الفجوة التنافسية (ما لا يقوله المنافسون)
- عوامل التسعير (بدون أرقام)
- مشاريع + قبل/بعد
- FAQ: 6-10 أسئلة واقعية
- روابط داخلية: 3-5 خدمات مكملة + مقالات داعمة

### Residential (home, villa, apartment)
- Hero: داخل حقيقي جميل
- Trust Layer: من سيأتي؟ آمن للمنزل؟
- الفرق بين أنواع التنظيف (عادي/عميق/دوري)
- ما يشمله / ما لا يشمله بوضوح
- سعر: عوامل المساحة + نوع المكان
- FAQ: سلامة الأسرة والممتلكات
- CTA: «أرسل صورة للتقييم»

### Specialized/Technical (مكيفات، خزانات، مسابح)
- تفاصيل تقنية حقيقية (كيف يُنفَّذ، متى يُستدعى)
- Safety & Compliance المعلومات
- الفرق بين التنظيف الدوري والعميق
- Owner Input Required: تأكيد القدرة التشغيلية + ترخيص

---

## متطلبات Owner Confirmation لكل خدمة مشروطة

| الخدمة | ما يُحتاج |
|---|---|
| pest-control | ترخيص رش مبيدات (هيئة حماية البيئة) + نوع المواد المستخدمة |
| pool-cleaning | قدرة تشغيلية + معدات الكلور والمعالجة |
| disinfection | مواد التعقيم المعتمدة + شهادة؟ |
| water-tank-cleaning | قدرة التسفير الداخلي للخزانات |
| ac-cleaning | هل يشمل فك وتركيب الوحدات؟ |
