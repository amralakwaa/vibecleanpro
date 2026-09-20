<?php

/**
 * Hub → spoke links for the Wave 2 pages.
 *
 * Two of the new pages (pools, pest control) had no inbound link at all,
 * and a page nobody links to is a page nobody finds. Each section below
 * is a real paragraph on the parent page with a real anchor in it - never
 * a link-table entry without text to back it (see .ai/rules/content.md).
 *
 * @return array<string, mixed>
 */
return [
    'villa-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'outdoor-extras',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>ما يحيط بالفيلا يحتاج عناية أيضًا</h2>',
                        '<p>أغلب من يطلب تنظيف فيلا يكتشف أن نصف الانطباع يصنعه ما هو خارج الجدران:</p>',
                        '<ul>',
                        '<li><a href="/services/courtyard-cleaning">تنظيف الحوش والممرات الخارجية</a> — الانترلوك والمداخل والأسوار وأثر الرمل والزيوت.</li>',
                        '<li><a href="/services/pool-cleaning">تنظيف المسبح</a> — الحوض وخط الماء والمحيط، مع حدود واضحة لما تشمله الخدمة.</li>',
                        '<li><a href="/services/marble-polishing">جلي وتلميع الرخام</a> — حين تكون الأرضية نظيفة لكنها فقدت لمعانها.</li>',
                        '</ul>',
                        '<p>ضمّها إلى الزيارة نفسها يوفّر تجهيز المعدات أكثر من مرة، ويصلك عرض سعر واحد يوضح كل بند.</p>',
                    ]],
                ],
            ],
        ],
    ],

    'home-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'wave2-links',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>حالات قريبة لها صفحاتها</h2>',
                        '<ul>',
                        '<li><strong>تسكن شقة؟</strong> قيود المبنى والمصعد ودورات المياه تجعل العمل مختلفًا — التفاصيل في <a href="/services/apartment-cleaning">تنظيف الشقق</a>.</li>',
                        '<li><strong>عندك مجلس بجلسات أرضية؟</strong> المساند والمفارش والسجاد تُعامل كوحدة في <a href="/services/majlis-cleaning">تنظيف المجالس</a>.</li>',
                        '<li><strong>الأرضية نظيفة لكنها بلا لمعان؟</strong> هذه حالة <a href="/services/marble-polishing">الجلي والتلميع</a> لا التنظيف.</li>',
                        '<li><strong>تتكرر الحشرات رغم التنظيف؟</strong> غالبًا المصدر تسرّب أو منفذ لم يُعالج — ابدأ بفحص في <a href="/services/pest-control">مكافحة الحشرات</a>.</li>',
                        '</ul>',
                    ]],
                ],
            ],
            [
                'section' => 'kitchen-bath-floors',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>تنظيف المطابخ ودورات المياه</h2>',
                        '<p>هاتان المساحتان تستهلكان وحدهما نحو نصف وقت أي تنظيف عميق، ولهما مواد وأدوات تختلف عن بقية البيت:</p>',
                        '<ul>',
                        '<li><strong>المطبخ:</strong> إزالة الدهون المتراكمة عن الخزائن وواجهات الأجهزة والجدار خلف الموقد، وتنظيف الشفاط من الخارج، والحوض والخلاط، وداخل الخزائن عند الطلب.</li>',
                        '<li><strong>دورات المياه:</strong> الأطقم والمغاسل والفواصل الزجاجية، ومعالجة الترسبات الكلسية على الخلاطات والرشاشات، والمرايا دون خطوط.</li>',
                        '<li><strong>ما بعد التنظيف:</strong> يمكن إضافة <a href="/services/disinfection">تطهير الأسطح كثيرة اللمس</a> في الزيارة نفسها.</li>',
                        '</ul>',
                        '<h2>الأرضيات: تنظيف أم استعادة لمعان؟</h2>',
                        '<p>ليست كل مشكلة أرضية مشكلة أوساخ. إن كانت الأرضية نظيفة لكنها باهتة أو عليها خطوط دقيقة تظهر تحت الإضاءة، فالتنظيف لن يعيدها — تلك حالة <a href="/services/marble-polishing">الجلي والتلميع</a>. أما السجاد والموكيت فلهما طريقتهما في <a href="/services/carpet-cleaning">تنظيف السجاد والموكيت</a>. نخبرك بأي الحالتين تناسب أرضيتك بعد الفحص.</p>',
                    ]],
                ],
            ],
        ],
    ],

    'disinfection' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'wave2-links',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>التعقيم لا يعالج مشكلة الحشرات</h2>',
                        '<p>يخلط كثيرون بين الأمرين: التطهير يقلل الميكروبات على الأسطح، ولا علاقة له بوجود حشرات في المكان. فإن كانت المشكلة صراصير أو نملًا أو بق فراش، فالعلاج يبدأ بفحص يحدد المصدر ومنافذ الدخول — وهو ما تشرحه صفحة <a href="/services/pest-control">مكافحة الحشرات</a>.</p>',
                        '<p>وكثيرًا ما يكون الترتيب الصحيح: معالجة الآفة أولًا، ثم <a href="/services/home-cleaning">تنظيف عميق</a>، ثم التطهير.</p>',
                    ]],
                ],
            ],
        ],
    ],

    'office-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'wave2-links',
                'blocks' => [
                    ['type' => 'rich_text', 'html' => [
                        '<h2>زيارة واحدة أم برنامج مستمر؟</h2>',
                        '<p>التنظيف لمرة واحدة يحلّ حالة، لا يحافظ على مستوى. والمنشأة التي يدخلها موظفون وزوار كل يوم تحتاج جدولًا معلنًا ونطاق عمل مكتوب وفريقًا ثابتًا — وهذا ما توضحه صفحة <a href="/services/cleaning-contracts">عقود النظافة الدورية</a>.</p>',
                        '<p>وإن كانت منشأتك محلًا أو معرضًا بواجهة زجاجية وحركة عملاء، فالتفاصيل التشغيلية تختلف، وتجدها في <a href="/services/shop-cleaning">تنظيف المحلات والمعارض</a>.</p>',
                    ]],
                ],
            ],
        ],
    ],
];
