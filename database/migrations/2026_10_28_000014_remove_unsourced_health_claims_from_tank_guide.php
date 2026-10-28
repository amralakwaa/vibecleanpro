<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes health and regulatory claims from the water-tank guide that no
 * source supports (a "general health recommendation" of 6/3 months, WHO
 * conformity, "approved" chlorine, illness as a warning sign) and that
 * contradict the site's own policy of making no medical claims. Replacement
 * wording mirrors /blog/water-tank-cleaning-how-often-riyadh.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const REPLACEMENTS = [
        'والتعقيم بمواد معتمدة.' => 'والتعقيم بمواد مناسبة لخزانات مياه الشرب.',
        'التوصية الصحية العامة: تنظيف الخزان مرة واحدة على الأقل كل 6 أشهر. في المناطق ذات جودة المياه المتغيرة أو في المنازل ذات الاستخدام العالي، ينصح بالتنظيف كل 3 أشهر.' => 'لا توجد وصفة واحدة تناسب كل منزل: يزيد تكرار التنظيف مع كثرة الاستخدام أو قرب مصادر الغبار وأعمال البناء، والمهم ألا يُترك الخزان سنوات دون تنظيف.',
        "<li>أمراض معوية متكررة بين أفراد الأسرة</li>\n" => '',
        '<li>مرور أكثر من 6 أشهر دون تنظيف</li>' => '<li>مرور وقت طويل منذ آخر تنظيف، أو عدم معرفتك متى نُظّف أصلًا</li>',
        '<strong>التعقيم بالكلور المعتمد</strong> بتركيز محدد وفق معايير الصحة، ثم ترك لفترة التلامس المطلوبة.' => '<strong>التعقيم</strong> بمواد مناسبة لخزانات مياه الشرب، مع ترك مدة التلامس المطلوبة.',
        'يضيف إليه مرحلة القضاء على البكتيريا والفيروسات بمواد مطابقة لمعايير منظمة الصحة العالمية.' => 'يضيف إليه مرحلة تعقيم الأسطح الداخلية بمواد مناسبة لمياه الشرب بعد التنظيف.',
        'هي الطريقة الوحيدة التي تضمن نتيجة حقيقية.' => 'لأنها تتيح الوصول إلى الرواسب في القاع وإلى الجدران.',
        '<li>مواد تعقيم معتمدة صحياً وآمنة للشرب بعد الشطف</li>' => '<li>مواد تعقيم مناسبة لخزانات مياه الشرب، مع شطف كامل قبل إعادة التعبئة</li>',
    ];

    public function up(): void
    {
        $this->apply(self::REPLACEMENTS);
    }

    public function down(): void
    {
        $reverse = [];

        foreach (self::REPLACEMENTS as $old => $new) {
            if ($new !== '') {
                $reverse[$new] = $old;
            }
        }

        $reverse['<li>مرور وقت طويل منذ آخر تنظيف، أو عدم معرفتك متى نُظّف أصلًا</li>'] = "<li>أمراض معوية متكررة بين أفراد الأسرة</li>\n<li>مرور أكثر من 6 أشهر دون تنظيف</li>";

        $this->apply($reverse);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function apply(array $replacements): void
    {
        $pageId = DB::table('pages')->where('slug', 'water-tank-cleaning-guide-riyadh')->where('pageable_type', 'article')->value('id');

        if ($pageId === null) {
            return;
        }

        foreach (DB::table('content_blocks')->where('page_id', $pageId)->where('type', 'rich_text')->get() as $block) {
            $data = json_decode($block->data, true);
            $content = (string) ($data['content'] ?? '');
            $updated = str_replace(array_keys($replacements), array_values($replacements), $content);

            if ($updated !== $content) {
                $data['content'] = $updated;
                DB::table('content_blocks')->where('id', $block->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
            }
        }
    }
};
