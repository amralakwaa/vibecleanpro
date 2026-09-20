<?php

/**
 * Service Entity Map — one owner per query.
 *
 * This is SEO organisation, not page copy: nothing here is rendered. It
 * records, for every service, the single query that page is allowed to
 * own, the phrases it may support, the intent behind them, and the pages
 * that should surround it. ServiceEntityMapTest holds it to its promises:
 * one primary keyword can belong to exactly one service (that is the
 * anti-cannibalisation guard), and every slug referenced must exist.
 *
 * Search intent vocabulary:
 *   transactional - ready to hire, asking who does it
 *   commercial    - comparing scope or price before hiring
 *   informational - wants to understand the work first
 *
 * Every service in the catalogue is published (owner decision,
 * 2026-09-20), so every slug here owns its queries in the live site -
 * pools, pest control and periodic contracts included.
 *
 * @return array<string, array{primary: string, secondary: list<string>, intent: string, related: list<string>, articles: list<string>}>
 */
return [
    'home-cleaning' => [
        'primary' => 'تنظيف منازل بالرياض',
        'secondary' => ['شركة تنظيف منازل بالرياض', 'تنظيف منزلي عميق', 'تنظيف بيوت بالرياض', 'أسعار تنظيف المنازل'],
        'intent' => 'transactional',
        'related' => ['apartment-cleaning', 'villa-cleaning', 'sofa-cleaning', 'carpet-cleaning', 'disinfection'],
        'articles' => ['cleaning-prices-riyadh', 'how-to-choose-cleaning-company-riyadh'],
    ],

    'villa-cleaning' => [
        'primary' => 'تنظيف فلل بالرياض',
        'secondary' => ['شركة تنظيف فلل', 'تنظيف فيلا كاملة', 'تكلفة تنظيف فيلا بالرياض', 'تنظيف فلل بعد البناء'],
        'intent' => 'transactional',
        'related' => ['home-cleaning', 'courtyard-cleaning', 'facade-cleaning', 'marble-polishing'],
        'articles' => ['villa-cleaning-cost-riyadh', 'villa-cleaning-checklist', 'courtyard-interlock-cleaning'],
    ],

    'apartment-cleaning' => [
        'primary' => 'تنظيف شقق بالرياض',
        'secondary' => ['تنظيف شقة قبل السكن', 'تنظيف شقة بعد الإخلاء', 'شركة تنظيف شقق', 'تنظيف شقق مفروشة'],
        'intent' => 'transactional',
        'related' => ['home-cleaning', 'post-construction-cleaning', 'sofa-cleaning', 'ac-cleaning'],
        'articles' => ['cleaning-prices-riyadh'],
    ],

    'office-cleaning' => [
        'primary' => 'تنظيف مكاتب بالرياض',
        'secondary' => ['شركة تنظيف شركات', 'تنظيف مقرات إدارية', 'نطاق عمل تنظيف المكاتب', 'تنظيف مكاتب خارج الدوام'],
        'intent' => 'transactional',
        'related' => ['cleaning-contracts', 'glass-cleaning', 'shop-cleaning', 'disinfection', 'carpet-cleaning'],
        'articles' => ['office-cleaning-scope-of-work', 'how-to-choose-cleaning-company-riyadh', 'cleaning-prices-riyadh'],
    ],

    'facade-cleaning' => [
        'primary' => 'تنظيف واجهات المباني بالرياض',
        'secondary' => ['تنظيف زجاج المباني', 'تنظيف واجهات زجاجية', 'تنظيف واجهات بالحبال', 'تكلفة تنظيف الواجهات'],
        'intent' => 'transactional',
        'related' => ['glass-cleaning', 'shop-cleaning', 'office-cleaning', 'post-construction-cleaning', 'courtyard-cleaning'],
        'articles' => ['facade-cleaning-cost-riyadh', 'facade-access-rope-lift-scaffold', 'streak-free-glass-cleaning-riyadh'],
    ],

    // Glass owns the pane; the facade owns the building. The split is
    // deliberate: "تنظيف زجاج المباني" stays with facade-cleaning because
    // that search wants height access, while this page takes the window,
    // partition and shopfront queries it was losing to it.
    'glass-cleaning' => [
        'primary' => 'تنظيف زجاج بالرياض',
        'secondary' => ['شركة تنظيف زجاج بالرياض', 'تنظيف نوافذ بالرياض', 'تنظيف قواطع زجاجية', 'تنظيف زجاج المحلات', 'تنظيف زجاج داخلي'],
        'intent' => 'transactional',
        'related' => ['facade-cleaning', 'office-cleaning', 'shop-cleaning', 'post-construction-cleaning'],
        'articles' => ['streak-free-glass-cleaning-riyadh'],
    ],

    'post-construction-cleaning' => [
        'primary' => 'تنظيف بعد البناء بالرياض',
        'secondary' => ['تنظيف بعد التشطيب', 'تنظيف قبل التسليم', 'إزالة مخلفات البناء', 'تنظيف عمائر بعد البناء'],
        'intent' => 'transactional',
        'related' => ['marble-polishing', 'water-tank-cleaning', 'ac-cleaning', 'apartment-cleaning'],
        'articles' => ['post-construction-handover-checklist', 'cleaning-prices-riyadh'],
    ],

    'ac-cleaning' => [
        'primary' => 'تنظيف مكيفات بالرياض',
        'secondary' => ['غسيل مكيفات', 'تنظيف مكيف سبليت', 'تنظيف مكيفات مركزية', 'تنظيف فلاتر المكيف'],
        'intent' => 'transactional',
        'related' => ['home-cleaning', 'villa-cleaning', 'disinfection'],
        'articles' => ['cleaning-prices-riyadh'],
    ],

    'water-tank-cleaning' => [
        'primary' => 'تنظيف خزانات المياه بالرياض',
        'secondary' => ['تعقيم خزانات', 'تنظيف خزان أرضي', 'تنظيف خزان علوي', 'شركة تنظيف خزانات'],
        'intent' => 'transactional',
        'related' => ['villa-cleaning', 'post-construction-cleaning', 'disinfection'],
        'articles' => ['cleaning-prices-riyadh'],
    ],

    'disinfection' => [
        'primary' => 'تعقيم منازل بالرياض',
        'secondary' => ['شركة تعقيم وتطهير', 'تطهير الأسطح', 'تعقيم مكاتب', 'الفرق بين التنظيف والتعقيم'],
        'intent' => 'commercial',
        'related' => ['home-cleaning', 'office-cleaning', 'pest-control'],
        'articles' => ['how-to-choose-cleaning-company-riyadh'],
    ],

    'sofa-cleaning' => [
        'primary' => 'تنظيف كنب بالرياض',
        'secondary' => ['غسيل كنب', 'تنظيف مفروشات', 'تنظيف مراتب', 'تنظيف كنب في المنزل'],
        'intent' => 'transactional',
        'related' => ['carpet-cleaning', 'majlis-cleaning', 'home-cleaning'],
        'articles' => ['cleaning-prices-riyadh'],
    ],

    'carpet-cleaning' => [
        'primary' => 'تنظيف سجاد بالرياض',
        'secondary' => ['غسيل موكيت', 'تنظيف موكيت المكاتب', 'تنظيف سجاد في المكان', 'تنظيف سجاد صوف'],
        'intent' => 'transactional',
        'related' => ['sofa-cleaning', 'majlis-cleaning', 'office-cleaning'],
        'articles' => ['cleaning-prices-riyadh'],
    ],

    'majlis-cleaning' => [
        'primary' => 'تنظيف مجالس بالرياض',
        'secondary' => ['تنظيف جلسات أرضية', 'غسيل مجالس', 'تنظيف مساند ومفارش', 'تنظيف مجلس قبل المناسبة'],
        'intent' => 'transactional',
        'related' => ['sofa-cleaning', 'carpet-cleaning', 'home-cleaning'],
        'articles' => [],
    ],

    'shop-cleaning' => [
        'primary' => 'تنظيف محلات بالرياض',
        'secondary' => ['تنظيف معارض', 'تنظيف واجهة محل', 'تنظيف محلات بعد الدوام', 'تنظيف معارض سيارات'],
        'intent' => 'transactional',
        'related' => ['office-cleaning', 'glass-cleaning', 'facade-cleaning', 'cleaning-contracts', 'marble-polishing'],
        'articles' => ['office-cleaning-scope-of-work'],
    ],

    'marble-polishing' => [
        'primary' => 'جلي رخام بالرياض',
        'secondary' => ['تلميع رخام', 'جلي بلاط', 'إزالة خدوش الرخام', 'جلي وتلميع أرضيات'],
        'intent' => 'commercial',
        'related' => ['post-construction-cleaning', 'villa-cleaning', 'shop-cleaning'],
        'articles' => ['cleaning-prices-riyadh'],
    ],

    'courtyard-cleaning' => [
        'primary' => 'تنظيف أحواش بالرياض',
        'secondary' => ['تنظيف انترلوك', 'تنظيف ممرات خارجية', 'إزالة بقع الزيت من الانترلوك', 'تنظيف مداخل وأسوار'],
        'intent' => 'transactional',
        'related' => ['villa-cleaning', 'facade-cleaning', 'pool-cleaning'],
        'articles' => ['courtyard-interlock-cleaning'],
    ],

    // ---- held at Review until the owner confirms the operations ----

    'pool-cleaning' => [
        'primary' => 'تنظيف مسابح بالرياض',
        'secondary' => ['تنظيف حوض المسبح', 'تنظيف خط الماء', 'تنظيف مسبح فيلا'],
        'intent' => 'transactional',
        'related' => ['villa-cleaning', 'courtyard-cleaning'],
        'articles' => [],
    ],

    'pest-control' => [
        'primary' => 'مكافحة حشرات بالرياض',
        'secondary' => ['رش مبيدات', 'مكافحة بق الفراش', 'مكافحة الصراصير', 'مكافحة القوارض'],
        'intent' => 'transactional',
        'related' => ['disinfection', 'home-cleaning'],
        'articles' => [],
    ],

    'cleaning-contracts' => [
        'primary' => 'عقود نظافة دورية بالرياض',
        'secondary' => ['عقد نظافة شهري', 'شركة نظافة للمنشآت', 'عقود نظافة للشركات', 'نظافة يومية للمكاتب'],
        'intent' => 'commercial',
        'related' => ['office-cleaning', 'shop-cleaning', 'disinfection'],
        'articles' => ['office-cleaning-scope-of-work', 'how-to-choose-cleaning-company-riyadh'],
    ],
];
