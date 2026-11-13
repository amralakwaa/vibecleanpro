# Blog audit — 2026-11-13

**المهارة:** `blog-audit` (المحلل `analyze_blog.py --batch`) · **المصدر:** HTML الحي لـ 23 مقالًا · البيانات الخام: `blog-audit-2026-11-13.json`

## اللوحة

| المؤشر | العدد |
|---|---|
| المقالات | 23 |
| متوسط درجة المحلل | 60/100 (56–62) |
| 90+ / 70–89 / 50–69 / أقل من 50 | 0 / 0 / 23 / 0 |
| مقالات بلا رابط وارد من مقال آخر | 2 (`ac-cleaning-when-why-riyadh`، `streak-free-glass-cleaning-riyadh`) |
| مقالات برابط وارد واحد | 5 |
| مقالات بلا روابط صادرة | 0 |
| تداخل موضوعي يحتاج تمييزًا | 2 زوج |
| محتوى قديم | 0 (كل التحديثات بين 2026-09-21 و2026-10-27) |

## لماذا الدرجة 60 ولا تعني ضعف المحتوى

المحلل مكتوب للإنجليزية، ويعمل هنا دون `beautifulsoup4` و`textstat`، فيقرأ الصفحة كاملة بما فيها القائمة والتذييل. نتائجه المرفوضة:

- **«3 صور بلا alt»:** هي الشعار الزخرفي `logo-mark.webp` بـ `alt=""` و`aria-hidden="true"` في الرأس والقائمة والتذييل. هذا هو الصحيح للصور الزخرفية. صور المحتوى كلها لها alt.
- **«العنوان لا يطابق موضوع الصفحة»** و**keyword_placement = 0**: مقارنة رموز لاتينية على نص عربي.
- **«لا تعريفات كيانات (is/are)»**: النمط إنجليزي فقط.
- **Flesch ونطاق قراءة الجمل:** مقاييس إنجليزية لا تصلح للعربية.
- **external_tier3 = 46:** روابط التذييل وواتساب والشبكات الاجتماعية، لا روابط داخل المقال.

النتائج المقبولة: الأصالة مثبتة سابقًا (تداخل 2–8% مع b4clean)، ولكل مقال ملخص يجيب أولًا، و6 أسئلة شائعة، وبيانات Article وBreadcrumb وFAQPage. وFAQPage لا يعطي نتائج منسّقة منذ مايو 2026، فهو للفهم فقط.

## درجات كل مقال

| المقال | الدرجة | المحتوى/30 | SEO/25 | E-E-A-T/15 | التقني/15 | الاستشهاد/15 |
|---|---|---|---|---|---|---|
| annual-cleaning-contracts-riyadh | 62 | 18 | 16 | 9 | 11 | 8 |
| palace-large-property-cleaning-riyadh | 62 | 18 | 16 | 9 | 11 | 8 |
| specialized-team-vs-domestic-workers-riyadh | 62 | 18 | 16 | 9 | 11 | 8 |
| water-tank-cleaning-guide-riyadh | 62 | 18 | 16 | 9 | 11 | 8 |
| ac-cleaning-when-why-riyadh | 61 | 16 | 16 | 9 | 12 | 8 |
| courtyard-interlock-cleaning | 61 | 16 | 16 | 9 | 12 | 8 |
| disinfection-when-needed-riyadh | 61 | 16 | 16 | 9 | 12 | 8 |
| facade-access-rope-lift-scaffold | 61 | 16 | 16 | 9 | 12 | 8 |
| facade-cleaning-cost-riyadh | 61 | 16 | 16 | 9 | 12 | 8 |
| marble-polishing-when-why-riyadh | 61 | 16 | 16 | 9 | 12 | 8 |
| office-cleaning-scope-of-work | 61 | 16 | 16 | 9 | 12 | 8 |
| pest-control-treatment-vs-prevention-riyadh | 61 | 16 | 16 | 9 | 12 | 8 |
| post-construction-handover-checklist | 61 | 16 | 16 | 9 | 12 | 8 |
| streak-free-glass-cleaning-riyadh | 61 | 16 | 16 | 9 | 12 | 8 |
| hourly-cleaning-riyadh | 60 | 16 | 16 | 9 | 11 | 8 |
| apartment-move-in-out-cleaning-riyadh | 59 | 16 | 14 | 9 | 12 | 8 |
| sofa-majlis-cleaning-cost-riyadh | 59 | 16 | 14 | 9 | 12 | 8 |
| sofa-majlis-cleaning-steam-or-wash-riyadh | 59 | 16 | 14 | 9 | 12 | 8 |
| villa-cleaning-checklist | 59 | 16 | 14 | 9 | 12 | 8 |
| water-tank-cleaning-how-often-riyadh | 59 | 14 | 16 | 9 | 12 | 8 |
| how-to-choose-cleaning-company-riyadh | 57 | 14 | 14 | 9 | 12 | 8 |
| villa-cleaning-cost-riyadh | 57 | 14 | 14 | 9 | 12 | 8 |
| cleaning-prices-riyadh | 56 | 14 | 14 | 9 | 11 | 8 |

## الروابط الداخلية بين المقالات

الروابط الصادرة من كل مقال تأتي من كتلة «مقالات ذات صلة» (`rankByTopic`)، ولذلك لا يصل الرابط إلى المقالات التي تشترك مع غيرها في خدمات أقل. الإصلاح المقترح (السكربت جاهز، ولم يُطبّق بعد): سطر «للتوسع» واحد في آخر نص المقال المصدر.

| المقال الهدف | الروابط الواردة | المقالات التي ستربط إليه |
|---|---|---|
| ac-cleaning-when-why-riyadh | 0 | disinfection-when-needed، annual-cleaning-contracts |
| streak-free-glass-cleaning-riyadh | 0 | facade-access-rope-lift-scaffold، villa-cleaning-checklist |
| courtyard-interlock-cleaning | 1 | palace-large-property-cleaning |
| hourly-cleaning-riyadh | 1 | specialized-team-vs-domestic-workers |
| post-construction-handover-checklist | 1 | apartment-move-in-out-cleaning |
| sofa-majlis-cleaning-steam-or-wash-riyadh | 1 | sofa-majlis-cleaning-cost |
| water-tank-cleaning-guide-riyadh | 1 | water-tank-cleaning-how-often |

## تداخل الموضوعات

| الموضوع | المقالان | التوصية |
|---|---|---|
| تكرار تنظيف الخزان | water-tank-cleaning-guide («الخطوات والتكرار…») و water-tank-cleaning-how-often | **تمييز:** يبقى «التكرار» لمقال how-often، ويركز الدليل على الخطوات وما بعد التنظيف، ويُحذف «والتكرار» من عنوانه. وكلمة «والضمانات» في العنوان تحتاج مراجعة لأنها لا تسمّي ضمانًا محددًا |
| تنظيف الكنب | sofa-majlis-cleaning-cost و sofa-majlis-cleaning-steam-or-wash | النية مختلفة (السعر مقابل الطريقة). يكفي الربط المتبادل |
| الواجهات | facade-cleaning-cost و facade-access-rope-lift-scaffold | النية مختلفة. لا إجراء |

## قائمة الإجراءات (من الأقل درجة)

| # | المقال | الإجراء |
|---|---|---|
| 1 | cleaning-prices-riyadh | هو المحور (22 رابطًا واردًا). لا أرقام جديدة إلا من مصدر المالك |
| 2 | how-to-choose / villa-cleaning-cost | لا إجراء على المحتوى. الخصم سببه أنماط إنجليزية |
| 3 | 7 مقالات ضعيفة الروابط الواردة | تطبيق `blog_links.php` (9 روابط) بعد الموافقة |
| 4 | water-tank-cleaning-guide | تمييز العنوان عن how-often (بعد الموافقة) |
| 5 | كل المقالات | إشارة E-E-A-T الأقوى خارجية: اسم الكاتب الكامل («منال أحمد») وصفحة كاتب. وهذا قرار المالك |
