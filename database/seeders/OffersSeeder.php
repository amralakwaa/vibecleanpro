<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 7 professional offers for Vibe Clean Pro's local environment.
 *
 * Each offer: creates the Offer record, a published Page (type=offer),
 * SEO metadata, rich-text content blocks, and links to the relevant
 * services. Idempotent: skips any offer whose page slug already exists.
 *
 * No fake prices — offer_price is null unless a clear package rate is
 * justified. discount_label describes the offer concept, never a made-up
 * percentage.
 */
class OffersSeeder extends Seeder
{
    /** Service IDs as confirmed in the production DB */
    private const SERVICES = [
        'home-cleaning' => 1,
        'villa-cleaning' => 2,
        'apartment-cleaning' => 3,
        'sofa-cleaning' => 4,
        'majlis-cleaning' => 5,
        'carpet-cleaning' => 6,
        'office-cleaning' => 7,
        'post-construction-cleaning' => 10,
        'pool-cleaning' => 14,
        'disinfection' => 15,
        'cleaning-contracts' => 17,
        'glass-cleaning' => 19,
    ];

    /** Media library IDs (confirmed present) */
    private const MEDIA = [
        'villa' => 2,   // media/library/villa-cleaning.jpg
        'apartment' => 3,   // media/library/apartment-cleaning.jpg
        'upholstery' => 4,   // media/library/upholstery-carpet-cleaning.jpg
        'office' => 5,   // media/library/office-cleaning.jpg
        'post-construction' => 6,  // media/library/post-construction-cleaning.jpg
        'glass' => 7,   // media/library/glass-facade-cleaning.jpg
        'commercial' => 12,  // media/library/b2b-commercial-team.jpg
        'disinfection' => 528, // media/covers/disinfection-cover.webp
        'majlis-cover' => 518, // media/covers/majlis-cleaning-cover.webp
    ];

    public function run(): void
    {
        $now = now()->toDateTimeString();

        $offers = $this->offers();

        foreach ($offers as $data) {
            if (Page::query()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $offer = Offer::create([
                'title' => $data['title'],
                'discount_label' => $data['discount_label'],
                'featured_media_id' => $data['media_id'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => true,
                'sort_order' => $data['sort_order'],
            ]);

            $page = Page::create([
                'type' => PageType::Offer,
                'pageable_type' => 'offer',
                'pageable_id' => $offer->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'status' => PageStatus::Published,
                'published_at' => $now,
                'sort_order' => $data['sort_order'],
            ]);

            DB::table('seo_metadata')->insert([
                'page_id' => $page->id,
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'robots_index' => 1,
                'robots_follow' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($data['blocks'] as $position => $block) {
                $page->contentBlocks()->create([
                    'type' => $block['type'],
                    'position' => $position + 1,
                    'is_active' => true,
                    'data' => $block['data'],
                ]);
            }

            $offer->services()->sync($data['service_ids']);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function offers(): array
    {
        $endOfYear = '2026-12-31';

        return [
            [
                'sort_order' => 1,
                'title' => 'العرض الملكي لتنظيف الفلل',
                'slug' => 'royal-villa-offer',
                'discount_label' => 'تنظيف شامل',
                'media_id' => self::MEDIA['villa'],
                'starts_at' => null,
                'ends_at' => $endOfYear,
                'service_ids' => [self::SERVICES['villa-cleaning'], self::SERVICES['home-cleaning']],
                'meta_title' => 'العرض الملكي لتنظيف الفلل | فايب كلين برو',
                'meta_description' => 'تنظيف شامل للفلل في الرياض: الطوابق والمطابخ والحمامات والأرضيات والنوافذ والتفاصيل النهائية — فريق متخصص، معاينة مجانية، عرض سعر مكتوب.',
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<h2>ما يشمله العرض</h2>
<ul>
<li>تنظيف عميق لجميع الطوابق والغرف</li>
<li>المطابخ: الأسطح والأرفف والأجهزة والشفاطات</li>
<li>الحمامات والمراحيض: تعقيم كامل بمواد معتمدة</li>
<li>الأرضيات: كنس وتنظيف وجلي عند الطلب</li>
<li>النوافذ والأبواب والأطر من الداخل</li>
<li>درج السلالم والمداخل والاستقبال</li>
<li>التفاصيل النهائية: أسقف، كريات النور، المفاتيح</li>
</ul>
<p>المعاينة مجانية. نرسل لك عرض سعر مكتوبًا قبل التنفيذ. لا يتم الدفع عبر الموقع.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'تحتاج تنظيف فيلتك؟',
                            'button_label' => 'اطلب عرض سعر',
                        ],
                    ],
                ],
            ],
            [
                'sort_order' => 2,
                'title' => 'باقة المنزل المتكامل',
                'slug' => 'complete-home-package',
                'discount_label' => 'باقة متكاملة',
                'media_id' => self::MEDIA['apartment'],
                'starts_at' => null,
                'ends_at' => $endOfYear,
                'service_ids' => [self::SERVICES['home-cleaning'], self::SERVICES['sofa-cleaning'], self::SERVICES['carpet-cleaning']],
                'meta_title' => 'باقة المنزل المتكامل | فايب كلين برو',
                'meta_description' => 'باقة تنظيف متكاملة للمنازل في الرياض تشمل تنظيف المنزل والكنب والسجاد والتعقيم — فريق متخصص، عرض سعر مكتوب قبل التنفيذ.',
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<h2>ما تشمله الباقة</h2>
<ul>
<li>تنظيف شامل للمنزل: غرف النوم، الصالة، المطبخ، الحمامات</li>
<li>تنظيف الكنب والأرائك بآلات البخار المتخصصة</li>
<li>تنظيف السجاد والموكيت وإزالة البقع</li>
<li>الأرضيات: كنس وتنظيف وتلميع</li>
<li>تعقيم المناطق كثيرة الاستخدام</li>
<li>تنظيف الأجهزة والأسطح والرفوف</li>
</ul>
<p>فريق متخصص يصل إليك في الموعد المتفق عليه. نرسل عرض سعر مكتوبًا بعد المعاينة.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'احجز باقة المنزل المتكامل',
                            'button_label' => 'اطلب عرض سعر',
                        ],
                    ],
                ],
            ],
            [
                'sort_order' => 3,
                'title' => 'عرض الانتقال الذكي',
                'slug' => 'smart-move-offer',
                'discount_label' => 'قبل السكن وبعد الإخلاء',
                'media_id' => self::MEDIA['post-construction'],
                'starts_at' => null,
                'ends_at' => $endOfYear,
                'service_ids' => [self::SERVICES['apartment-cleaning'], self::SERVICES['post-construction-cleaning']],
                'meta_title' => 'عرض الانتقال الذكي | تنظيف قبل السكن وبعد الإخلاء | فايب كلين برو',
                'meta_description' => 'تنظيف متخصص للانتقال في الرياض: تنظيف شقق قبل السكن وبعد الإخلاء — مطابخ، حمامات، أرضيات، وكل ركن بعناية دقيقة.',
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<h2>خدمة مخصصة لوقت الانتقال</h2>
<p>سواء كنت تنتقل إلى منزل جديد أو تسلّم منزلك لمالكه، نوفر لك تنظيفًا متخصصًا يعيد المكان إلى أفضل حالاته.</p>
<h3>تنظيف قبل السكن</h3>
<ul>
<li>إزالة الغبار والشحنات من التعبئة والنقل</li>
<li>تنظيف الخزائن والأرفف والأدراج من الداخل</li>
<li>المطابخ والحمامات تعقيم كامل</li>
<li>الأرضيات والجدران وإطارات النوافذ</li>
</ul>
<h3>تنظيف بعد الإخلاء</h3>
<ul>
<li>إزالة كل آثار الإقامة</li>
<li>تنظيف عميق للمطبخ بما فيه الأجهزة</li>
<li>الحمامات: تعقيم وإزالة التكلس والصدأ</li>
<li>الأرضيات وإزالة آثار الأثاث</li>
</ul>
<p>نرسل لك عرض سعر مكتوبًا بعد المعاينة. لا دفع مسبق عبر الموقع.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'تحتاج تنظيف عند الانتقال؟',
                            'button_label' => 'اطلب عرض سعر',
                        ],
                    ],
                ],
            ],
            [
                'sort_order' => 4,
                'title' => 'باقة المكاتب الاحترافية',
                'slug' => 'professional-office-package',
                'discount_label' => 'للشركات والمكاتب',
                'media_id' => self::MEDIA['office'],
                'starts_at' => null,
                'ends_at' => $endOfYear,
                'service_ids' => [self::SERVICES['office-cleaning'], self::SERVICES['cleaning-contracts']],
                'meta_title' => 'باقة المكاتب الاحترافية | فايب كلين برو',
                'meta_description' => 'تنظيف احترافي للمكاتب والشركات في الرياض: تنظيف يومي أو أسبوعي، الزجاج والأرضيات والمساحات المشتركة، عقد نظافة دوري.',
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<h2>لماذا باقة المكاتب الاحترافية؟</h2>
<p>بيئة العمل النظيفة تعكس احترافية مؤسستك وتحسّن إنتاجية فريقك. نوفر لك جدول تنظيف منتظمًا وموثقًا.</p>
<h2>ما تشمله الباقة</h2>
<ul>
<li>تنظيف المكاتب والطاولات والكراسي</li>
<li>الزجاج والنوافذ الداخلية</li>
<li>الأرضيات: كنس وتنظيف يومي</li>
<li>مناطق الاستقبال والممرات</li>
<li>المطابخ والمناطق المشتركة</li>
<li>الحمامات: تنظيف وتعقيم</li>
<li>جدول زيارات مكتوب وتقرير بعد كل زيارة</li>
</ul>
<p>نقدم عقود نظافة دورية مرنة: يومية، أسبوعية، أو شهرية حسب حجم منشأتك.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'تحتاج نظافة دورية لمكتبك؟',
                            'button_label' => 'اطلب عرض سعر',
                        ],
                    ],
                ],
            ],
            [
                'sort_order' => 5,
                'title' => 'عرض التعقيم الشامل',
                'slug' => 'full-disinfection-offer',
                'discount_label' => 'تعقيم بمواد معتمدة',
                'media_id' => self::MEDIA['disinfection'],
                'starts_at' => null,
                'ends_at' => $endOfYear,
                'service_ids' => [self::SERVICES['disinfection']],
                'meta_title' => 'عرض التعقيم الشامل | فايب كلين برو',
                'meta_description' => 'تعقيم شامل للمنازل والمنشآت في الرياض بمواد معتمدة وصديقة للبيئة — تغطية كاملة للمناطق كثيرة الاستخدام.',
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<h2>التعقيم الحقيقي يختلف عن التنظيف</h2>
<p>التنظيف يزيل الأوساخ، أما التعقيم فيقضي على الجراثيم والبكتيريا في المناطق التي يلمسها كل أحد. نستخدم مواد معتمدة صحيًا وآمنة على الأطفال والحيوانات الأليفة.</p>
<h2>ما يشمله عرض التعقيم</h2>
<ul>
<li>رش ومسح المناطق كثيرة الاستخدام بمادة معقمة معتمدة</li>
<li>المطابخ: أسطح التحضير، الثلاجة، الميكروويف، الصنبور</li>
<li>الحمامات: تعقيم كامل بما فيها حوض الاستحمام ومقبض الباب</li>
<li>غرف النوم: الأسطح والمقابض والمفاتيح</li>
<li>الصالة والمدخل والدرابزين</li>
<li>مفاتيح الكهرباء ومقابض الأبواب في كل الأرجاء</li>
</ul>
<p>المواد المستخدمة آمنة وفق متطلبات الجهات الصحية. شهادة تعقيم متاحة عند الطلب.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'تحتاج تعقيمًا شاملًا لمكانك؟',
                            'button_label' => 'اطلب عرض سعر',
                        ],
                    ],
                ],
            ],
            [
                'sort_order' => 6,
                'title' => 'باقة المجالس الفاخرة',
                'slug' => 'luxury-majlis-package',
                'discount_label' => 'مجالس وكنب وسجاد',
                'media_id' => self::MEDIA['majlis-cover'],
                'starts_at' => null,
                'ends_at' => $endOfYear,
                'service_ids' => [self::SERVICES['majlis-cleaning'], self::SERVICES['sofa-cleaning'], self::SERVICES['carpet-cleaning']],
                'meta_title' => 'باقة تنظيف المجالس الفاخرة | فايب كلين برو',
                'meta_description' => 'باقة تنظيف متخصصة للمجالس في الرياض: كنب، سجاد، ستائر، مناطق الضيافة — تنظيف عميق بآلات البخار وإزالة البقع.',
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<h2>المجلس يعكس ذوقك</h2>
<p>نقدم خدمة تنظيف متخصصة لمجالس الضيافة والكنب الفاخر، بآلات بخار متطورة وعمالة مدربة على التعامل مع الأقمشة الثمينة.</p>
<h2>ما تشمله الباقة</h2>
<ul>
<li>تنظيف الكنب والأرائك بآلات البخار</li>
<li>تنظيف السجاد والبسط وإزالة البقع العنيدة</li>
<li>الستائر والوسائد: تنظيف وتعطير</li>
<li>المجلس العربي: الفرش والمساند والوسائد الأرضية</li>
<li>الطاولات والأسطح الخشبية</li>
<li>تعطير المكان بعطور طبيعية راقية</li>
</ul>
<p>نستخدم منظفات آمنة على جميع أنواع الأقمشة والجلود. المعاينة قبل التنفيذ، والعرض مكتوب.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'احجز تنظيف مجلسك الآن',
                            'button_label' => 'اطلب عرض سعر',
                        ],
                    ],
                ],
            ],
            [
                'sort_order' => 7,
                'title' => 'الاشتراك الشهري الذكي',
                'slug' => 'monthly-smart-subscription',
                'discount_label' => 'زيارات دورية',
                'media_id' => self::MEDIA['commercial'],
                'starts_at' => null,
                'ends_at' => null,
                'service_ids' => [self::SERVICES['home-cleaning'], self::SERVICES['cleaning-contracts']],
                'meta_title' => 'الاشتراك الشهري الذكي | نظافة دورية للمنازل | فايب كلين برو',
                'meta_description' => 'اشتراك نظافة شهري للمنازل والمنشآت في الرياض: جدول زيارات ثابت، فريق متخصص، تقرير بعد كل زيارة.',
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<h2>لماذا الاشتراك الشهري أذكى؟</h2>
<p>بدلًا من الانتظار حتى يتراكم الأوساخ، نضع لك جدولًا دوريًا ثابتًا يحافظ على نظافة مكانك باستمرار — بتكلفة أقل وجهد لا تحسه.</p>
<h2>ما يتضمنه الاشتراك</h2>
<ul>
<li>عدد زيارات متفق عليه شهريًا (أسبوعية أو نصف شهرية)</li>
<li>نفس الفريق في كل زيارة للاستمرارية</li>
<li>جدول زيارات مكتوب مسبقًا</li>
<li>تقرير بعد كل زيارة</li>
<li>تنظيف شامل في كل زيارة: الغرف والمطبخ والحمامات والأرضيات</li>
<li>تنظيف دوري للكنب والسجاد حسب الجدول</li>
</ul>
<p>الاشتراك قابل للتعديل في أي وقت. لا رسوم إلغاء مسبقة. ابدأ بزيارة واحدة واطلع على النتيجة.</p>',
                        ],
                    ],
                    [
                        'type' => 'cta',
                        'data' => [
                            'heading' => 'ابدأ اشتراكك الشهري اليوم',
                            'button_label' => 'اطلب عرض سعر',
                        ],
                    ],
                ],
            ],
        ];
    }
}
