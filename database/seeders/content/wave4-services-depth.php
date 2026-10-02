<?php

/**
 * Wave 4: depth sections for the concise commercial service pages (append mode).
 *
 * Purpose: raise the thinner, high-demand service pages to the gold-reference
 * section set (facade / post-construction) WITHOUT touching their titles, H1,
 * slug, canonical or existing blocks — these pages are already indexed and
 * starting to rank, so nothing that carries traction is changed. Each section
 * is additive and carries a unique `section` key, so re-running the seeder adds
 * nothing twice (see ServiceContentSeeder::appendSections).
 *
 * House rules kept: written from professional practice; prices are always
 * factors, never numbers; no invented certificate/body/number; the "up to 10
 * years" warranty is NEVER placed on a consumable cleaning service where it
 * reads as implausible (see .ai/rules/content.md). Trust is expressed through
 * service-appropriate signals (real extraction, written quote, honesty about
 * limits, Saudi trained team). Internal links only point at pages that exist.
 *
 * @return array<string, mixed>
 */
return [
    // ================= تنظيف الكنب =================
    'sofa-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'problems-and-health',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>ما الذي يعالجه تنظيف الكنب فعليًا؟</h2>',
                        '<p>الكنب من أكثر قطع البيت استخدامًا وأقلها تنظيفًا عميقًا. مع الوقت يختزن القماش والإسفنج الداخلي ما لا تلتقطه المكنسة السطحية:</p>',
                        '<ul>',
                        '<li><strong>غبار وعثّ الغبار</strong> — يتراكم داخل النسيج والحشوة، وهو من أكثر مسببات الحساسية وانسداد الأنف المنزلي شيوعًا.</li>',
                        '<li><strong>خلايا جلد وبقايا طعام</strong> — تتسرب بين الوسائد وتتحلل ببطء فتظهر رائحة لا يخفيها التعطير.</li>',
                        '<li><strong>وبر الحيوانات الأليفة</strong> — يلتصق بعمق في الألياف ويصعب شفطه سطحيًا.</li>',
                        '<li><strong>بقع ممتصة</strong> — قهوة وعصير وحليب أطفال وعرق، تثبت في الإسفنج إن لم تُعالَج بالاستخلاص.</li>',
                        '<li><strong>دهون الجسم</strong> على المساند والأذرع، تترك تغيّر لون تدريجيًا يصعب رجوعه لاحقًا.</li>',
                        '</ul>',
                        '<p>التنظيف السطحي يزيل ما تراه العين فقط. ما نقوم به هو <strong>استخلاص</strong> يسحب المذاب والمعلّق من داخل النسيج، لا مجرد مسح للوجه الظاهر. ولمن في البيت حساسية أو ربو، أو أطفال وحيوانات، أو بعد فترة مرض في المنزل، يُنصح بإضافة <a href="/services/disinfection">التعقيم والتطهير</a> بعد التنظيف.</p>',
                    ]],
                ],
            ],
            [
                'section' => 'cost-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>ما الذي يحدد تكلفة تنظيف الكنب؟</h2>',
                        '<p>الكنب يُسعَّر على ما يحتاجه فعلًا، لا على عدد القطع وحده:</p>',
                        '<ul>',
                        '<li><strong>عدد القطع وحجمها</strong> — كنب زاوية كبير يختلف عن كرسيين منفردين.</li>',
                        '<li><strong>نوع القماش</strong> — المخمل والأقمشة الحساسة تحتاج عناية ومنتجات تختلف عن القطن والبوليستر.</li>',
                        '<li><strong>درجة الاتساخ</strong> — كنب لم يُنظَّف منذ سنوات يحتاج عملًا مضاعفًا.</li>',
                        '<li><strong>البقع الخاصة</strong> — بقعة قديمة أو دهنية قد تحتاج معالجة إضافية قبل الغسيل.</li>',
                        '<li><strong>مكان التنفيذ</strong> — داخل منزلك مباشرة، مع حماية الأرضية والجدار المجاور أثناء العمل.</li>',
                        '</ul>',
                        '<p>نطلب صورة للكنب ونوع القماش، ثم يصلك عرض سعر مكتوب قبل البدء، ولا يتم الدفع عبر الموقع. وللمقارنة الصحيحة بين العروض راجع <a href="/blog/cleaning-prices-riyadh">دليل أسعار خدمات التنظيف في الرياض</a>.</p>',
                    ]],
                    ['type' => 'rich_text', 'html' => [
                        '<h2>لماذا فايب كلين برو لتنظيف الكنب؟</h2>',
                        '<ul>',
                        '<li><strong>استخلاص فعلي لا مسح سطحي</strong> — نسحب المذاب من داخل النسيج ونضبط الرطوبة حتى لا يبقى القماش مبللًا.</li>',
                        '<li><strong>فريق سعودي مدرب</strong> على التعامل مع كل نوع قماش دون إضعاف الألياف أو تغيير لونها.</li>',
                        '<li><strong>صدق قبل البدء</strong> — إن كانت بقعة لا تُزال بالكامل نقولها لك قبل العمل لا بعده.</li>',
                        '<li><strong>عرض سعر مكتوب</strong> يوضح القطع والنطاق والمدة المتوقعة للتجفيف.</li>',
                        '</ul>',
                    ]],
                    ['type' => 'cta', 'heading' => 'أرسل صورة الكنب ونوع القماش ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات تكمل تنظيف الكنب</h2>',
                        '<p>غالبًا ما يُنظَّف الكنب ضمن تجهيز المجلس أو الصالة كاملة:</p>',
                        '<ul>',
                        '<li><a href="/services/carpet-cleaning">تنظيف السجاد والموكيت</a> — السجادة أسفل الكنب تجمع الغبار نفسه، وتنظيفهما معًا يوحّد النتيجة.</li>',
                        '<li><a href="/services/majlis-cleaning">تنظيف المجالس</a> — الجلسات الأرضية والمساند والمفارش بعناية تناسب القماش.</li>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — حين يكون تنظيف الكنب جزءًا من تنظيف شامل للبيت.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],
];
