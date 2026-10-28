<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Blog content fixes from the 2026-10-28 SEO content review:
 * - removes a water-tank cross-link that landed in the sofa-cost article;
 * - moves the summary sections of three articles above the CTA and FAQ,
 *   dropping the one exact duplicate after keeping its unique point;
 * - opens seven articles with a sentence that answers the title question,
 *   built only from facts already stated in each article.
 *
 * Every edit is located by text, not ID, and skips when the text is absent.
 */
return new class extends Migration
{
    private const STRAY_TANK_LINK = "\n\n<p>هذه المقالة تجيب عن <em>متى</em> تنظّف الخزان. لمعرفة <em>كيف</em> يتم التنظيف الاحترافي خطوة بخطوة والفرق بين التنظيف البسيط والتعقيم الكامل اقرأ: <a href=\"/blog/water-tank-cleaning-guide-riyadh\">دليل تنظيف خزانات المياه — الخطوات والمعايير والضمانات</a>.</p>";

    private const PRICES_LIST_ANCHOR = "<li>ما الذي لا يشمله العرض، وهل هناك رسوم إضافية محتملة؟</li>\n";

    private const PRICES_LIST_ADDITION = "<li>هل يعرض صور أعمال حقيقية موثقة، لا صورًا استرشادية؟</li>\n";

    private const DUPLICATE_COMPARE_SECTION = '<h2>كيف تقارن بين عروض الأسعار؟</h2><ol><li>تأكد أن كل عرض يصف نفس نطاق العمل — الغرف المشمولة، المهام المطلوبة، هل تشمل الزجاج والواجهة.</li><li>اسأل عن المستهلكات: هل المواد والمعدات في السعر أم تُحسب منفصلة؟</li><li>افحص صور الأعمال الحقيقية — ليس الصور الاسترشادية.</li><li>الأرخص دون توضيح نطاق العمل يعني مفاجآت لاحقًا.</li></ol>';

    /**
     * Block order per article, each entry [type, needle in content or null].
     *
     * @var array<string, array{new: list<array{0: string, 1: string|null}>, old: list<array{0: string, 1: string|null}>}>
     */
    private const ORDER = [
        'cleaning-prices-riyadh' => [
            'new' => [['rich_text', 'ليس لها سعر موحد'], ['rich_text', 'طرق التسعير الشائعة'], ['rich_text', 'أكثر عوامل السعر تأثيرًا'], ['rich_text', 'أسعار خدمات محددة'], ['cta', null], ['faq', null]],
            'old' => [['rich_text', 'ليس لها سعر موحد'], ['rich_text', 'طرق التسعير الشائعة'], ['cta', null], ['faq', null], ['rich_text', 'أكثر عوامل السعر تأثيرًا'], ['rich_text', 'كيف تقارن بين عروض الأسعار؟'], ['rich_text', 'أسعار خدمات محددة']],
        ],
        'post-construction-handover-checklist' => [
            'new' => [['rich_text', 'قبل استلام عقارك بعد التشطيب'], ['rich_text', 'قبل أن تبدأ الفحص'], ['rich_text', 'نقاط الفحص الحرجة'], ['rich_text', 'متى تطلب تنظيف ما بعد البناء'], ['cta', null], ['faq', null]],
            'old' => [['rich_text', 'قبل استلام عقارك بعد التشطيب'], ['rich_text', 'قبل أن تبدأ الفحص'], ['cta', null], ['faq', null], ['rich_text', 'نقاط الفحص الحرجة'], ['rich_text', 'متى تطلب تنظيف ما بعد البناء']],
        ],
        'facade-cleaning-cost-riyadh' => [
            'new' => [['rich_text', 'يُحسب بالمتر المربع ويتأثر'], ['rich_text', 'مساحة الواجهة لا مساحة المبنى'], ['rich_text', 'كيف يُحسب سعر تنظيف الواجهة؟'], ['rich_text', 'ما الذي يرفع التكلفة'], ['cta', null], ['faq', null]],
            'old' => [['rich_text', 'يُحسب بالمتر المربع ويتأثر'], ['rich_text', 'مساحة الواجهة لا مساحة المبنى'], ['cta', null], ['faq', null], ['rich_text', 'كيف يُحسب سعر تنظيف الواجهة؟'], ['rich_text', 'ما الذي يرفع التكلفة']],
        ],
    ];

    /** @var array<string, string> */
    private const OPENINGS = [
        'sofa-majlis-cleaning-steam-or-wash-riyadh' => 'البخار يناسب التنظيف الدوري والأقمشة المتينة ويجف أسرع، والغسيل والاستخلاص أقوى للاتساخ الكثيف والبقع المتغلغلة، أما الأقمشة الحساسة والألوان التي تنزف فتُعالَج بطريقة منخفضة الرطوبة بعد اختبار زاوية مخفية.',
        'ac-cleaning-when-why-riyadh' => 'نظّف المكيف في الرياض قبل موسم الصيف وبعده، وبتكرار أعلى قرب مواقع البناء أو الشوارع الترابية، أو فورًا إذا ضعف التبريد أو ظهرت رائحة أو غبار أو تنقيط ماء.',
        'sofa-majlis-cleaning-cost-riyadh' => 'سعر تنظيف الكنب والمجالس بالرياض يتحدد بعدد القطع والمقاعد، ونوع القماش، وطريقة التنظيف، ودرجة الاتساخ والبقع، والبنود الإضافية كالسجاد والستائر — ولذلك يصلك مكتوبًا بعد صور أو معاينة.',
        'pest-control-treatment-vs-prevention-riyadh' => 'إن كانت الحشرات ظاهرة الآن فأنت تحتاج معالجة: فحص يحدد النوع والمصدر ثم معالجة موجّهة ومتابعة؛ وإن لم تكن هناك إصابة فالوقاية برنامج دوري لنقاط الدخول يقلّل احتمال ظهورها.',
        'apartment-move-in-out-cleaning-riyadh' => 'قبل السكن يركّز التنظيف على جاهزية الشقة: الخزائن من الداخل والمطبخ ودورات المياه والأرضيات والنوافذ؛ وبعد الإخلاء يُضاف إليه إزالة أثر الساكن السابق من بقع وأتربة متراكمة خلف الأبواب وفوق الخزائن.',
        'marble-polishing-when-why-riyadh' => 'الجلي يزيل طبقة سطحية رقيقة بأقراص متدرّجة لإخفاء الخدوش وفروق البلاط، والتلميع يعيد اللمعان فقط؛ الرخام السليم الذي بهت لمعانه يكفيه التلميع، أما مع الخدوش وفروق الارتفاع فيحتاج الجلي أولًا.',
        'disinfection-when-needed-riyadh' => 'تحتاج تطهيرًا أو تعقيمًا بعد مرض أحد أفراد المنزل، أو قبل السكن وبعد الإخلاء، وفي المرافق المشتركة ومناطق الطعام والأسطح كثيرة اللمس — وفي كل الحالات يأتي التنظيف أولًا لأن المطهّر لا يعمل فوق الأوساخ.',
    ];

    public function up(): void
    {
        $this->replaceInBlock('sofa-majlis-cleaning-cost-riyadh', self::STRAY_TANK_LINK, '');

        $this->replaceInBlock('cleaning-prices-riyadh', self::PRICES_LIST_ANCHOR, self::PRICES_LIST_ANCHOR.self::PRICES_LIST_ADDITION, self::PRICES_LIST_ADDITION);

        $pageId = $this->articlePageId('cleaning-prices-riyadh');
        $duplicate = $pageId ? $this->findBlock($pageId, 'rich_text', 'كيف تقارن بين عروض الأسعار؟') : null;

        if ($duplicate !== null) {
            DB::table('content_blocks')->where('id', $duplicate->id)->delete();
        }

        foreach (self::ORDER as $slug => $order) {
            $this->applyOrder($slug, $order['new']);
        }

        foreach (self::OPENINGS as $slug => $sentence) {
            $pageId = $this->articlePageId($slug);

            if ($pageId === null || $this->findBlock($pageId, 'rich_text', $sentence) !== null) {
                continue;
            }

            DB::table('content_blocks')->where('page_id', $pageId)->increment('position');
            DB::table('content_blocks')->insert([
                'page_id' => $pageId,
                'type' => 'rich_text',
                'position' => 0,
                'is_active' => true,
                'data' => json_encode(['content' => "<p>{$sentence}</p>"], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::OPENINGS as $slug => $sentence) {
            $pageId = $this->articlePageId($slug);
            $block = $pageId ? $this->findBlock($pageId, 'rich_text', $sentence) : null;

            if ($block !== null) {
                DB::table('content_blocks')->where('id', $block->id)->delete();
                DB::table('content_blocks')->where('page_id', $pageId)->decrement('position');
            }
        }

        if (($pageId = $this->articlePageId('cleaning-prices-riyadh')) && $this->findBlock($pageId, 'rich_text', 'كيف تقارن بين عروض الأسعار؟') === null) {
            DB::table('content_blocks')->insert([
                'page_id' => $pageId,
                'type' => 'rich_text',
                'position' => 99,
                'is_active' => true,
                'data' => json_encode(['content' => self::DUPLICATE_COMPARE_SECTION], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::ORDER as $slug => $order) {
            $this->applyOrder($slug, $order['old']);
        }

        $this->replaceInBlock('cleaning-prices-riyadh', self::PRICES_LIST_ADDITION, '');

        $pageId = $this->articlePageId('sofa-majlis-cleaning-cost-riyadh');
        $block = $pageId ? $this->findBlock($pageId, 'rich_text', 'كيف تحصل على عرض دقيق') : null;

        if ($block !== null) {
            $data = json_decode($block->data, true);

            if (! str_contains($data['content'], 'هذه المقالة تجيب عن')) {
                $data['content'] .= self::STRAY_TANK_LINK;
                DB::table('content_blocks')->where('id', $block->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    /**
     * @param  list<array{0: string, 1: string|null}>  $order
     */
    private function applyOrder(string $slug, array $order): void
    {
        $pageId = $this->articlePageId($slug);

        if ($pageId === null) {
            return;
        }

        $blocks = array_map(fn (array $entry) => $this->findBlock($pageId, $entry[0], $entry[1]), $order);

        if (in_array(null, $blocks, true)) {
            return;
        }

        foreach ($blocks as $position => $block) {
            DB::table('content_blocks')->where('id', $block->id)->update(['position' => $position, 'updated_at' => now()]);
        }
    }

    private function replaceInBlock(string $slug, string $search, string $replace, ?string $skipIfPresent = null): void
    {
        $pageId = $this->articlePageId($slug);
        $block = $pageId ? $this->findBlock($pageId, 'rich_text', $search) : null;

        if ($block === null) {
            return;
        }

        $data = json_decode($block->data, true);

        if ($skipIfPresent !== null && str_contains($data['content'], $skipIfPresent)) {
            return;
        }

        $data['content'] = str_replace($search, $replace, $data['content']);
        DB::table('content_blocks')->where('id', $block->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }

    private function findBlock(int $pageId, string $type, ?string $needle): ?object
    {
        return DB::table('content_blocks')
            ->where('page_id', $pageId)
            ->where('type', $type)
            ->get()
            ->first(fn ($block) => $needle === null || str_contains((string) (json_decode($block->data, true)['content'] ?? ''), $needle));
    }

    private function articlePageId(string $slug): ?int
    {
        return DB::table('pages')->where('slug', $slug)->where('pageable_type', 'article')->value('id');
    }
};
