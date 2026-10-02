<?php

/**
 * Wave 5: real field-work video evidence on the matching service pages
 * (append mode). Each clip is placed on the service it actually shows - no
 * neighbourhood or date claims, honest captions. The video block renders
 * through x-public.video-evidence (click-to-load facade), so it never touches
 * the page's critical path or the 97/100 mobile performance.
 *
 * The clips + webp posters live under storage/app/public/media/videos
 * (gitignored, deployed with the rest of the media). Each section carries a
 * unique key so re-running the seeder adds nothing twice.
 *
 * @return array<string, mixed>
 */
return [
    'glass-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'video-evidence',
                'blocks' => [
                    ['type' => 'video', 'heading' => 'من أعمالنا — تنظيف الزجاج', 'items' => [
                        ['slug' => 'glass-window-01', 'caption' => 'تنظيف نافذة زجاجية كبيرة'],
                        ['slug' => 'glass-window-02', 'caption' => 'نوافذ ممتدة من الأرض للسقف'],
                        ['slug' => 'pressure-wash-screens', 'caption' => 'غسيل مناخل النوافذ بمكينة ضغط'],
                    ]],
                ],
            ],
        ],
    ],

    'facade-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'video-evidence',
                'blocks' => [
                    ['type' => 'video', 'heading' => 'من أعمالنا — تنظيف الواجهات وأعمال الارتفاع', 'items' => [
                        ['slug' => 'glass-height-01', 'caption' => 'تنظيف جدار زجاجي مرتفع'],
                        ['slug' => 'glass-height-02', 'caption' => 'تنظيف نافذة مرتفعة من على السلّم'],
                    ]],
                ],
            ],
        ],
    ],

    'courtyard-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'video-evidence',
                'blocks' => [
                    ['type' => 'video', 'heading' => 'من أعمالنا — الأرضيات الخارجية', 'items' => [
                        ['slug' => 'courtyard-scrub', 'caption' => 'فرك وغسيل أرضيات خارجية'],
                    ]],
                ],
            ],
        ],
    ],

    'post-construction-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'video-evidence',
                'blocks' => [
                    ['type' => 'video', 'heading' => 'من أعمالنا — ما بعد البناء', 'items' => [
                        ['slug' => 'post-construction-vacuum', 'caption' => 'شفط المياه والمخلفات بعد الغسيل العميق'],
                    ]],
                ],
            ],
        ],
    ],

    'home-cleaning' => [
        'mode' => 'append',
        'sections' => [
            [
                'section' => 'video-evidence',
                'blocks' => [
                    ['type' => 'video', 'heading' => 'من أعمالنا — تنظيف المنازل', 'items' => [
                        ['slug' => 'brand-floor', 'caption' => 'تنظيف الأرضيات بفريقنا'],
                        ['slug' => 'detail-woodwork', 'caption' => 'تنظيف تفاصيل الأعمال الخشبية'],
                    ]],
                ],
            ],
        ],
    ],
];
