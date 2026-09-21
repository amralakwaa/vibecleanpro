<?php

/**
 * Captions for the photographs that carry an argument - and only those.
 *
 * A caption is not a second alt text. Alt describes the picture for
 * someone who cannot see it; a caption tells a reader who can see it
 * what to notice. Attaching one to all 188 project photographs would add
 * 188 lines of noise and dilute the few that matter, so this file covers
 * three kinds of image only:
 *
 *   hero   - the photograph the page opens on
 *   before - the state the client recognises as their own problem
 *   after  - the state they are buying
 *
 * Keyed by source_ref, then by stage. The seeder applies a caption to the
 * FIRST image of that stage in the project's own sort order, which is the
 * one the template shows in the pair. Existing captions are never
 * overwritten.
 *
 * @return array<string, array<string, string>>
 */

return [
    // ---- The seven projects that document a stage change -------------
    'PRJ-CAND-20260313-01' => [
        'before' => 'قبل: أرضية وجدران دورة المياه تحمل أثر الاستخدام اليومي المتراكم.',
        'after' => 'بعد: الأسطح نفسها من الزاوية نفسها — هذه هي المقارنة التي نسلّمها للعميل.',
    ],
    'CAND-20260730-PAVERS-D' => [
        'before' => 'قبل: طبقة غبار متماسكة وبقع ثابتة بين فواصل الانترلوك — ما لا يزيله الكنس ولا الغسل السطحي.',
        'hero' => 'توثيق حالة السطح قبل العمل من عدة زوايا: مرجع يُقاس عليه ما بعده.',
    ],
    'PRJ-CAND-20260507-01' => [
        'before' => 'قبل: بقايا بناء وكابلات مكشوفة على بلاط لم يُستخدم بعد.',
        'hero' => 'العمل بجوار أثاث وكاونتر حجري مغطى للحماية — أصعب ما في التنظيف التسليمي.',
    ],
    'CAND-20260507-MODERN-VILLA' => [
        'after' => 'بعد: أرضية رخامية خالية من مخلفات البناء وزجاج نظيف، جاهزة للاستلام.',
        'hero' => 'النتيجة التي يستلمها المالك: مساحة داخلية مكتملة التشطيب بلا أثر بناء.',
    ],
    'CAND-20260614-VILLA-A' => [
        'after' => 'بعد: أرضية داخلية عاكسة عقب التنظيف.',
        'hero' => 'العمل يمتد من جدران الفيلا ونجارتها إلى ديكها الخشبي وأسطح الحديقة.',
    ],
    'CAND-20260730-INDUSTRIAL-C' => [
        'after' => 'بعد: خطوط تحديد ممرات الأمان عادت واضحة — أثر تشغيلي لا تجميلي.',
        'hero' => 'أرضية مستودع صناعي عقب الفرك الميكانيكي.',
    ],
    'CAND-20260822-INTERIOR' => [
        'after' => 'بعد: حافة كل درجة وزاويتها الداخلية — الموضعان اللذان يتجاوزهما التنظيف السريع.',
        'hero' => 'درج حجري داخلي عقب التنظيف والتفصيل اليدوي.',
    ],

    // ---- Heroes of the highest-priority case studies ------------------
    'CAND-20260729-FACADE-A' => [
        'hero' => 'العمل على الواجهات والزجاج المرتفع بالحبال والرافعات — أوسع توثيق مصوَّر في مكتبتنا.',
    ],
    'CAND-20260801-FACADE-B' => [
        'hero' => 'كسوة الواجهة أثناء التنظيف بمعدات وصول، مع توثيق المقارنة من الزاوية نفسها.',
    ],
    'CAND-20260730-FACADE-B' => [
        'hero' => 'ثلاثة فنيين معلّقين بالحبال على واجهة زجاجية مرتفعة — حيث لا تصل الرافعة.',
    ],
    'CAND-20260614-VILLA-01' => [
        'hero' => 'فريق ميداني بمعدات شفط رطبة وجافة داخل الفيلا، لا عاملًا بمكنسة.',
    ],
    'CAND-20260909-OFFICE-A' => [
        'hero' => 'العمل على مسار المقر الإداري كاملًا خارج ساعات الدوام.',
    ],
    'PRJ-CAND-20260305-01' => [
        'hero' => 'ثلاث جبهات في مشروع واحد: ماكينة على الأرضية، ويد على الحجر، وتفصيل في دورات المياه.',
    ],
    'CAND-20260523-GLASS-INTERIOR-01' => [
        'hero' => 'ألواح الزجاج الداخلية لوحًا بعد لوح — المعيار هو خلوّها من الأثر بزاوية الضوء.',
    ],
    'CAND-20260801-VILLA-A' => [
        'hero' => 'مسار متصل داخل فيلا مأهولة: من دورات المياه إلى الممرات فالأرضيات.',
    ],
    'CAND-20260523-CORRIDOR-01' => [
        'hero' => 'غسل الممر على مقاطع متتابعة مع إبقاء مسار مرور مفتوح للمرفق.',
    ],
    'CAND-20260614-INSTITUTIONAL-C' => [
        'hero' => 'دورات مياه منشأة مؤسسية: تغطية الأرضيات والمغاسل والكاونترات في مسار واحد.',
    ],
];
