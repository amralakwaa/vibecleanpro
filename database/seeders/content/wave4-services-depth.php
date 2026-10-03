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

    // ================= تنظيف المجالس =================
    'majlis-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'problems-and-health',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>ما الذي يعالجه تنظيف المجلس فعليًا؟</h2>',
                        '<p>المجلس هو أكثر مكان في البيت استقبالًا للضيوف، وأكثره تعرّضًا لما يصعب رؤيته. الجلسات الأرضية والمساند والمفارش تختزن مع الوقت:</p>',
                        '<ul>',
                        '<li><strong>دخان العود والبخور</strong> — يتشرّبه القماش والحشوة تدريجيًا فتبقى الرائحة حتى بعد التهوية.</li>',
                        '<li><strong>بقع القهوة والطعام</strong> — من الضيافة والمناسبات، تثبت في النسيج إن لم تُعالَج باستخلاص.</li>',
                        '<li><strong>غبار وعثّ الغبار</strong> في المساند والمفارش — من أكثر مسببات الحساسية في مجالس كثيرة الاستخدام.</li>',
                        '<li><strong>أثر كثرة الجلوس</strong> — تغيّر لون تدريجي على المساند والأطراف في المجالس التي تُستخدم يوميًا.</li>',
                        '</ul>',
                        '<p>التعطير يغطّي الرائحة ولا يزيلها. ما نقوم به هو تنظيف واستخلاص للنسيج نفسه. ولمن في البيت حساسية، أو قبل استقبال كبير، يُنصح بإضافة <a href="/services/disinfection">التعقيم والتطهير</a> للمساند وأكثر الأماكن لمسًا.</p>',
                    ]],
                ],
            ],
            [
                'section' => 'cost-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>لماذا فايب كلين برو لتنظيف المجالس؟</h2>',
                        '<ul>',
                        '<li><strong>استخلاص فعلي لا مسح سطحي</strong> — نعالج النسيج من الداخل ونضبط الرطوبة حتى لا يبقى المجلس مبللًا.</li>',
                        '<li><strong>فريق سعودي مدرب</strong> يفهم تكوين المجلس السعودي — جلسات أرضية ومساند ومفارش — ويتعامل مع كل قماش بما يناسبه.</li>',
                        '<li><strong>تنظيم حسب المناسبة</strong> — نحجز الموعد مبكرًا قبل المناسبة ليجف المجلس في وقته.</li>',
                        '<li><strong>عرض سعر مكتوب</strong> يوضح القطع والنطاق والمدة المتوقعة للتجفيف.</li>',
                        '</ul>',
                    ]],
                    ['type' => 'cta', 'heading' => 'مناسبة قادمة؟ أرسل صورة المجلس ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات تكمل تنظيف المجلس</h2>',
                        '<p>تجهيز المجلس للمناسبة غالبًا يشمل أكثر من الجلسات:</p>',
                        '<ul>',
                        '<li><a href="/services/carpet-cleaning">تنظيف السجاد والموكيت</a> — سجاد المجلس يحمل الغبار والبقع نفسها.</li>',
                        '<li><a href="/services/sofa-cleaning">تنظيف الكنب</a> — للمجالس التي تجمع بين الجلسات الأرضية والكنب.</li>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — حين يكون المجلس جزءًا من تجهيز البيت كاملًا للمناسبة.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف السجاد والموكيت =================
    'carpet-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'problems-and-health',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>ما الذي يعالجه تنظيف السجاد فعليًا؟</h2>',
                        '<p>السجاد والموكيت يعملان كمصفاة للبيت: يحجزان الغبار والجزيئات بدل أن تطير في الهواء — لكنهما يحتفظان بها حتى تُسحب فعلًا. ما يتراكم في الوبر مع الوقت:</p>',
                        '<ul>',
                        '<li><strong>غبار وعثّ الغبار</strong> في عمق الوبر — من أكثر مسببات الحساسية وانسداد الأنف المنزلي.</li>',
                        '<li><strong>رمل وأتربة ناعمة</strong> تستقر بين الألياف وتُضعف السجادة باحتكاكها عند المشي.</li>',
                        '<li><strong>بقع ممتصة</strong> — قهوة وعصير وحليب أطفال، تثبت في النسيج إن لم تُعالَج بالاستخلاص.</li>',
                        '<li><strong>وبر الحيوانات والروائح</strong> — تلتصق بعمق ولا يكفي الشفط السطحي لإزالتها.</li>',
                        '</ul>',
                        '<p>الشفط المنزلي يزيل السطح فقط. ما نقوم به هو غسيل واستخلاص يسحب المذاب والعالق من داخل الوبر. ولمن في البيت حساسية أو أطفال أو حيوانات، التنظيف العميق الدوري يُحدث فرقًا ملموسًا في جودة الهواء.</p>',
                    ]],
                ],
            ],
            [
                'section' => 'cost-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>لماذا فايب كلين برو لتنظيف السجاد؟</h2>',
                        '<ul>',
                        '<li><strong>غسيل واستخلاص لا شفط سطحي</strong> — نسحب المذاب من عمق الوبر ونضبط التجفيف.</li>',
                        '<li><strong>فريق سعودي مدرب</strong> يفرّق بين السجاد اليدوي والموكيت والمقاطف ويعامل كلًّا بما يناسب خامته.</li>',
                        '<li><strong>صدق قبل البدء</strong> — إن كانت بقعة لا تُزال بالكامل نقولها لك قبل العمل.</li>',
                        '<li><strong>عرض سعر مكتوب</strong> يوضح القطع والنطاق والمدة المتوقعة للتجفيف.</li>',
                        '</ul>',
                    ]],
                    ['type' => 'cta', 'heading' => 'أرسل صورة السجاد ونوعه ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات تكمل تنظيف السجاد</h2>',
                        '<ul>',
                        '<li><a href="/services/sofa-cleaning">تنظيف الكنب</a> — الكنب فوق السجادة يجمع الغبار نفسه، وتنظيفهما معًا يوحّد النتيجة.</li>',
                        '<li><a href="/services/majlis-cleaning">تنظيف المجالس</a> — سجاد المجلس مع الجلسات والمساند.</li>',
                        '<li><a href="/services/office-cleaning">تنظيف المكاتب</a> — لموكيت المكاتب والمقرات ضمن عقد نظافة منتظم.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف الشقق =================
    // (already has scope, pre-move/post-eviction, steps, cost factors — add
    //  a decision helper + trust, then complementary links.)
    'apartment-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'choice-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>أي تنظيف تحتاجه شقتك؟</h2>',
                        '<p>لا توجد شقة واحدة تحتاج الشيء نفسه. نسألك عن حالتك أولًا ثم نحدد النطاق:</p>',
                        '<ul>',
                        '<li><strong>تنظيف دوري</strong> — للشقة المسكونة التي تحتاج غسيلًا عميقًا للمطبخ ودورات المياه والأرضيات.</li>',
                        '<li><strong>قبل السكن</strong> — شقة جديدة أو مؤثثة حديثًا، تنظيف كامل قبل نقل الأثاث والسكن.</li>',
                        '<li><strong>بعد إخلاء المستأجر</strong> — إعادة الشقة لحالة تسليم نظيفة للمالك أو للمستأجر التالي.</li>',
                        '<li><strong>بعد ترميم أو دهان بسيط</strong> — إزالة الغبار الناعم وآثار العمل.</li>',
                        '</ul>',
                        '<p>نطلب وصفًا موجزًا أو صورًا، ثم يصلك عرض سعر مكتوب بالنطاق والمدة قبل البدء، ولا يتم الدفع عبر الموقع. فريق سعودي مدرب، ونطاق عمل واضح يحدد ما يشمله التنظيف وما لا يشمله قبل أن يبدأ الفريق.</p>',
                    ]],
                    ['type' => 'cta', 'heading' => 'صف لنا حالة شقتك ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات تكمل تنظيف الشقة</h2>',
                        '<ul>',
                        '<li><a href="/services/sofa-cleaning">تنظيف الكنب</a> و<a href="/services/carpet-cleaning">السجاد</a> — ضمن التنظيف الشامل أو قبل السكن.</li>',
                        '<li><a href="/services/ac-cleaning">تنظيف المكيفات</a> — مهم قبل السكن في شقة جديدة أو بعد فترة إغلاق.</li>',
                        '<li><a href="/services/disinfection">التعقيم والتطهير</a> — قبل انتقال أسرة جديدة إلى الشقة.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= جلي وتلميع الرخام =================
    // (already has when-you-need-polishing, steps, cost factors, polish-vs-clean
    //  comparison — add surfaces/problems depth + trust, then complementary.)
    'marble-polishing' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'surfaces-and-problems',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>الأسطح التي نتعامل معها وما تعالجه</h2>',
                        '<p>الجلي عملية ميكانيكية تُعيد السطح لا تُغطّيه، وتختلف حسب نوع الحجر:</p>',
                        '<ul>',
                        '<li><strong>الرخام الطبيعي</strong> — الأكثر طلبًا للجلي، يتأثر بالخدوش وبهتان اللون وآثار الأحماض (عصير، منظفات غير مناسبة).</li>',
                        '<li><strong>الجرانيت</strong> — أقسى، يحتاج أدوات تلميع أعلى درجة لإعادة اللمعان.</li>',
                        '<li><strong>الترازو والبلاط المصقول</strong> — أرضيات قديمة يعيد الجلي توحيد لونها ولمعانها.</li>',
                        '</ul>',
                        '<p>أكثر ما نعالجه: خدوش سطحية من الاستخدام والأثاث، بهتان ولمعان مفقود، فروق ارتفاع خفيفة بين البلاطات، وبقع متغلغلة لا يزيلها التنظيف العادي. أما الكسور العميقة والشروخ الهيكلية فخارج نطاق الجلي، ونوضح ذلك قبل البدء.</p>',
                    ]],
                    ['type' => 'rich_text', 'html' => [
                        '<h2>لماذا فايب كلين برو للجلي؟</h2>',
                        '<ul>',
                        '<li><strong>تدرّج صحيح في الأحجار</strong> — من إزالة الخدوش حتى التلميع النهائي على مراحل، لا قفزة واحدة تترك أثرًا.</li>',
                        '<li><strong>حماية المكان</strong> — عزل الغبار والماء عن بقية الأثاث والجدران أثناء العمل.</li>',
                        '<li><strong>صدق في التوقع</strong> — نوضح ما سيعود لمعانه بالكامل وما قد يبقى منه أثر قبل أن نبدأ.</li>',
                        '<li><strong>عرض سعر مكتوب</strong> بالمساحة والحالة والمراحل المطلوبة.</li>',
                        '</ul>',
                    ]],
                    ['type' => 'cta', 'heading' => 'أرسل صورة للأرضية ومساحتها ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/post-construction-cleaning">تنظيف ما بعد البناء</a> — الجلي غالبًا خطوة أخيرة بعد التشطيب وتنظيفه.</li>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — حين يكون الجلي ضمن تجهيز شامل للبيت.</li>',
                        '<li><a href="/services/facade-cleaning">تنظيف الواجهات</a> — للرخام والحجر الخارجي للمداخل والواجهات.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف المحلات والمنشآت التجارية =================
    // (already has scope, no-disruption steps, cost factors — add commercial
    //  types + trust, then complementary.)
    'shop-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'types-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>المحلات والمنشآت التي نخدمها</h2>',
                        '<p>نظافة المحل جزء من انطباع العميل الأول وقرار دخوله. نخدم:</p>',
                        '<ul>',
                        '<li><strong>المحلات والمعارض</strong> — واجهات زجاجية وأرضيات كثيرة الحركة وزوايا عرض.</li>',
                        '<li><strong>الصالونات والعيادات</strong> — تحتاج نظافة ظاهرة ومعايير أعلى للأسطح كثيرة اللمس.</li>',
                        '<li><strong>المطاعم والكافيهات (واجهات الاستقبال)</strong> — زجاج وأرضيات ومداخل تُرى من الشارع.</li>',
                        '<li><strong>المكاتب الصغيرة والفروع</strong> — ضمن جدولة خارج أوقات العمل.</li>',
                        '</ul>',
                        '<p>ننفّذ خارج ساعات الذروة أو بعد الإغلاق حتى لا يتعطّل عملك، بفريق سعودي مدرب وعرض سعر مكتوب يوضح النطاق قبل البدء. وللنظافة المستمرة بدل الزيارة الواحدة، راجع <a href="/services/cleaning-contracts">عقود النظافة الدورية</a>.</p>',
                    ]],
                    ['type' => 'cta', 'heading' => 'أخبرنا بنوع محلك ومساحته ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات تكمل تنظيف المحل</h2>',
                        '<ul>',
                        '<li><a href="/services/glass-cleaning">تنظيف الزجاج</a> — واجهة المحل الزجاجية هي أول ما يراه المار.</li>',
                        '<li><a href="/services/facade-cleaning">تنظيف الواجهات</a> — للواجهات المرتفعة والكلادينج واللوحات.</li>',
                        '<li><a href="/services/disinfection">التعقيم والتطهير</a> — للعيادات والصالونات والمنشآت كثيرة الزوار.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف الزجاج =================
    // (already has types, problems, steps, maintenance — add cost+trust, then
    //  complementary.)
    'glass-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'cost-and-trust',
                'blocks' => [
                    ['type' => 'price_factors', 'heading' => 'ما الذي يحدد تكلفة تنظيف الزجاج؟', 'items' => [
                        ['title' => 'عدد الألواح وحجمها', 'description' => 'نوافذ عادية تختلف عن واجهات زجاجية كبيرة.'],
                        ['title' => 'طريقة الوصول والارتفاع', 'description' => 'زجاج داخلي سهل الوصول يختلف عن زجاج مرتفع يحتاج معدات.'],
                        ['title' => 'نوع الاتساخ', 'description' => 'غبار عادي أم بقع ماء جيري ورواسب عسِرة تحتاج معالجة.'],
                        ['title' => 'الوجهان أم وجه واحد', 'description' => 'تنظيف الزجاج من الداخل والخارج معًا.'],
                    ], 'note' => null],
                    ['type' => 'rich_text', 'html' => [
                        '<p>نطلب صورة أو وصفًا للزجاج وموقعه، ثم يصلك عرض سعر مكتوب قبل البدء. للزجاج المرتفع وواجهات المباني، الخدمة الأنسب هي <a href="/services/facade-cleaning">تنظيف الواجهات</a> بفريق مجهّز لأعمال الارتفاع.</p>',
                    ]],
                    ['type' => 'cta', 'heading' => 'أرسل صورة للزجاج وموقعه ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/facade-cleaning">تنظيف الواجهات</a> — للزجاج المرتفع وواجهات المباني الزجاجية.</li>',
                        '<li><a href="/services/shop-cleaning">تنظيف المحلات</a> — لواجهات المحلات الزجاجية ضمن تنظيف شامل.</li>',
                        '<li><a href="/services/office-cleaning">تنظيف المكاتب</a> — لزجاج القواطع والواجهات الداخلية ضمن عقد منتظم.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف المسابح =================
    // (already has scope, steps, cost, exclusions — add why/maintenance + trust,
    //  then complementary.)
    'pool-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'why-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>لماذا يحتاج المسبح تنظيفًا منتظمًا؟</h2>',
                        '<p>مسبح الرياض يتعرض لعاملين دائمين: غبار محمول في الهواء، وشمس قوية تسرّع نمو الطحالب. وإهمال التنظيف يظهر بسرعة:</p>',
                        '<ul>',
                        '<li><strong>طحالب على الجدران والأرضية</strong> تجعل السطح زلقًا ولون الماء باهتًا.</li>',
                        '<li><strong>رواسب وغبار</strong> في القاع وعند المصافي.</li>',
                        '<li><strong>خط ماء متّسخ</strong> — الحد الدهني عند مستوى الماء من أكثر ما يُلاحَظ.</li>',
                        '<li><strong>أوراق ومخلفات</strong> تسدّ الفلاتر وتقلل كفاءة الدورة.</li>',
                        '</ul>',
                        '<p>خدمتنا هي تنظيف الحوض والبلاط وخط الماء والمحيط وفق نطاق واضح. أعمال معالجة كيمياء الماء وموازنته لها حدودها، ونوضح ما يشمله العمل وما لا يشمله قبل البدء، بفريق سعودي مدرب وعرض سعر مكتوب.</p>',
                    ]],
                    ['type' => 'cta', 'heading' => 'أرسل صورة للمسبح ومقاسه ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/courtyard-cleaning">تنظيف الأحواش والأرضيات الخارجية</a> — محيط المسبح والممرات حوله.</li>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — ضمن تجهيز شامل للفيلا.</li>',
                        '<li><a href="/services/water-tank-cleaning">تنظيف خزانات المياه</a> — للعناية بمصدر المياه في العقار.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف الأحواش والأرضيات الخارجية =================
    // (already has scope, steps, cost, interlock-vs-tile — add problems + trust,
    //  then complementary.)
    'courtyard-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'problems-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>ما الذي يعالجه تنظيف الأحواش والأرضيات الخارجية؟</h2>',
                        '<p>الأرضيات الخارجية في الرياض تتعرض لما لا تتعرض له الداخلية:</p>',
                        '<ul>',
                        '<li><strong>رمل وأتربة متراكمة</strong> بعد كل موسم غبار.</li>',
                        '<li><strong>أعشاب تنمو في فواصل الانترلوك</strong> وتفكّك ترتيبه مع الوقت.</li>',
                        '<li><strong>بقع زيوت وإطارات</strong> في مواقف السيارات.</li>',
                        '<li><strong>طحالب وبقع رطوبة</strong> في الزوايا قليلة الشمس وحول المسبح.</li>',
                        '</ul>',
                        '<p>ننظّف بما يناسب نوع الأرضية — الانترلوك يختلف عن البلاط المصقول — ونوضح ما يُزال بالكامل وما قد يبقى منه أثر قبل البدء، بفريق سعودي مدرب وعرض سعر مكتوب بالمساحة والحالة.</p>',
                    ]],
                    ['type' => 'cta', 'heading' => 'أرسل صورة للحوش ومساحته ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/facade-cleaning">تنظيف الواجهات</a> — واجهة الفيلا وأسوارها الخارجية.</li>',
                        '<li><a href="/services/pool-cleaning">تنظيف المسابح</a> — المسبح ومحيطه ضمن العناية الخارجية.</li>',
                        '<li><a href="/services/post-construction-cleaning">تنظيف ما بعد البناء</a> — لأحواش العقارات الجديدة بعد التشطيب.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= عقود النظافة =================
    // (already has what's-included, steps, contract-value factors, facility
    //  types — add benefits + trust, then complementary.)
    'cleaning-contracts' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'benefits-and-trust',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>لماذا عقد نظافة بدل الطلب كل مرة؟</h2>',
                        '<p>الزيارة المتفرقة تحل مشكلة اليوم، والعقد يمنع تكرارها. ما يضيفه العقد الدوري:</p>',
                        '<ul>',
                        '<li><strong>ثبات في النتيجة</strong> — نفس المعايير في كل زيارة، لا فرق بين مرة وأخرى.</li>',
                        '<li><strong>فريق يعرف منشأتك</strong> — نقاطها الحساسة وجدولها، فيقل الوقت الضائع في كل زيارة.</li>',
                        '<li><strong>جدولة بالأولوية</strong> — مواعيد ثابتة تناسب ساعات عملك.</li>',
                        '<li><strong>فاتورة واحدة ونطاق مكتوب</strong> — بدل تفاوض السعر في كل مرة.</li>',
                        '<li><strong>متابعة جودة</strong> — نقطة تواصل واحدة لأي ملاحظة ومعالجتها.</li>',
                        '</ul>',
                        '<p>نبدأ بزيارة تقييم لنطاق المنشأة وتكرار الزيارات المناسب، ثم يصلك عرض ونطاق عمل مكتوب. فريق سعودي مدرب، وشروط واضحة قبل التوقيع.</p>',
                    ]],
                    ['type' => 'cta', 'heading' => 'منشأتك تحتاج نظافة منتظمة؟ اطلب زيارة تقييم', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات تدخل عادة ضمن العقود</h2>',
                        '<ul>',
                        '<li><a href="/services/office-cleaning">تنظيف المكاتب والشركات</a> — العمود الأساسي لعقود المقرات الإدارية.</li>',
                        '<li><a href="/services/glass-cleaning">تنظيف الزجاج</a> و<a href="/services/facade-cleaning">الواجهات</a> — ضمن جدول دوري للمنشأة.</li>',
                        '<li><a href="/services/disinfection">التعقيم والتطهير</a> — للمنشآت كثيرة الزوار.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= مكافحة الحشرات =================
    // Scope-only per .ai/rules/content.md: NO licence number or licensing claim
    // of any kind. (already has what-we-handle, steps, safety — add approach +
    //  cost + complementary, all scope-only.)
    'pest-control' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'approach-and-cost',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>لماذا الفحص قبل الرش؟</h2>',
                        '<p>الرش العشوائي يخفّف المشكلة أيامًا ثم تعود. طريقتنا تبدأ بتحديد المصدر: من أين تدخل الحشرة، وأين تتكاثر، وما الذي يجذبها في المكان. المعالجة الموجّهة لهذه النقاط تدوم أطول من رشّ السطح وحده، وغالبًا تحتاج زيارة متابعة للتأكد من كسر دورة التكاثر.</p>',
                    ]],
                    ['type' => 'cta', 'heading' => 'صف لنا المشكلة والمكان ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/disinfection">التعقيم والتطهير</a> — خدمة مختلفة: التطهير يقتل الجراثيم ولا يعالج الحشرات، وقد تُطلبان معًا.</li>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — النظافة المنتظمة تقلّل ما يجذب الحشرات.</li>',
                        '<li><a href="/services/cleaning-contracts">عقود النظافة</a> — للمنشآت التي تحتاج وقاية دورية.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف المكيفات — ربط داخلي فقط (الصفحة مكتملة) =================
    'ac-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — تنظيف المكيفات غالبًا جزء من تجهيز البيت قبل الصيف.</li>',
                        '<li><a href="/services/post-construction-cleaning">تنظيف ما بعد البناء</a> — الغبار الناعم يستقر داخل الوحدات قبل أول تشغيل.</li>',
                        '<li><a href="/services/disinfection">التعقيم والتطهير</a> — للعناية بجودة الهواء الداخلي مع تنظيف الوحدات.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= تنظيف الخزانات =================
    // Scope per .ai/rules/content.md: cleaning + disinfection only; insulation,
    // repairs and plumbing are out of scope (already stated on the page).
    'water-tank-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'cost-and-trust',
                'blocks' => [
                    ['type' => 'cta', 'heading' => 'أخبرنا بسعة الخزان وموقعه ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/post-construction-cleaning">تنظيف ما بعد البناء</a> — أعمال البناء تترك رواسب في الخزان قبل أول استخدام.</li>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — ضمن العناية الدورية بالعقار.</li>',
                        '<li><a href="/services/pool-cleaning">تنظيف المسابح</a> — للعناية بمصادر المياه في الفيلا.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],

    // ================= التعقيم والتطهير =================
    'disinfection' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'cost-and-trust',
                'blocks' => [
                    ['type' => 'cta', 'heading' => 'أخبرنا بالمكان ومساحته ويصلك عرض مكتوب', 'label' => 'اطلب عرض سعر', 'url' => '/quote'],
                ],
            ],
            [
                'section' => 'complementary-services',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>خدمات ذات صلة</h2>',
                        '<ul>',
                        '<li><a href="/services/home-cleaning">تنظيف المنزل</a> — التنظيف أولًا ثم التطهير للنتيجة الكاملة.</li>',
                        '<li><a href="/services/sofa-cleaning">تنظيف الكنب</a> — للأسطح القماشية كثيرة الاستخدام.</li>',
                        '<li><a href="/services/office-cleaning">تنظيف المكاتب</a> — لتطهير المقرات كثيرة الموظفين والزوار.</li>',
                        '</ul>',
                    ]],
                ],
            ],
        ],
    ],
];
