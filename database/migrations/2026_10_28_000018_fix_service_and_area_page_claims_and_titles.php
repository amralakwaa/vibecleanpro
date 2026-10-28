<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Service/area audit fixes (2026-10-28):
 * - four meta titles that still opened with «افضل» (descriptions were fixed in 000013);
 * - an unsupported superlative heading (al-qirawan) and an open-ended
 *   «we guarantee results» (al-yasmin) aligned with the 24-hour re-clean on /warranty;
 * - FAQs for al-muhammadiyah, the one area page without any, built from its
 *   own documented projects and facts already published on the site.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> slug => [old title, new title] */
    private const TITLES = [
        'north-riyadh' => ['افضل شركة تنظيف شمال الرياض | تغطية 12 حياً بالصور قبل وبعد', 'تنظيف الفلل والشقق في شمال الرياض — 12 حيًا ومشاريع موثّقة | فايب كلين برو'],
        'east-riyadh' => ['افضل شركة تنظيف شرق الرياض | غسيل فلل الرمال والمونسية', 'شركة تنظيف في شرق الرياض — الرمال والمونسية | فايب كلين برو'],
        'central-riyadh' => ['افضل شركة تنظيف وسط الرياض | مكاتب العليا والمنطقة التجارية', 'شركة تنظيف في وسط الرياض — مكاتب العليا والمنطقة التجارية | فايب كلين برو'],
        'mosque-cleaning' => ['تنظيف مساجد بالرياض | افضل غسيل سجاد وتعقيم مواضئ', 'تنظيف المساجد بالرياض — سجاد بالبخار وتعقيم المواضئ | فايب كلين برو'],
    ];

    /** @var list<array{0: string, 1: string}> [old, new] text inside rich_text blocks */
    private const TEXT = [
        ['<strong>لماذا نحن الخيار الأفضل؟</strong>', '<strong>لماذا تختارنا لتنظيف ما بعد البناء في القيروان؟</strong>'],
        ['ونضمن لك النتائج.', 'وإن لم تطابق النتيجة ما اتُّفق عليه نعيد تنظيف الجزء محل الملاحظة مجانًا عند إبلاغنا خلال 24 ساعة.'],
    ];

    private const AREA_SLUG = 'al-muhammadiyah';

    /** @var list<array{0: string, 1: string}> */
    private const AREA_FAQS = [
        ['ما الأعمال التي نفّذتموها في حي المحمدية؟', 'أغلب ما نفّذناه في المحمدية تنظيف واجهات المباني: خمسة مشاريع موثّقة بالصور بين الحبال والرافعة والارتفاعات، إضافة إلى مشروع لتنظيف مكاتب. ونقدّم في الحي بقية خدماتنا للفلل والشقق والمنشآت.'],
        ['كيف تُنظَّف واجهات المباني المرتفعة في المحمدية؟', 'يُختار أسلوب الوصول حسب ارتفاع المبنى وشكل الواجهة: أعمدة تلسكوبية للارتفاعات المنخفضة، ورافعة سلة حين تتوفر مساحة وقوف أمام المبنى، والحبال للأبراج والواجهات التي لا تصلها الرافعات. ويُحدَّد ذلك بعد المعاينة أو الصور.'],
        ['كيف أحصل على عرض سعر لمبنى أو مكتب في المحمدية؟', 'أرسل صورًا للواجهة أو المكان ووصفًا لما تحتاجه عبر واتساب أو نموذج طلب عرض السعر، وفي المباني الكبيرة نرتب معاينة. يصلك نطاق العمل والسعر مكتوبين قبل البدء، ولا يتم الدفع عبر الموقع.'],
        ['ما الضمان على الخدمة في المحمدية؟', 'إذا لم تطابق النتيجة نطاق العمل المتفق عليه، نعيد تنظيف الجزء محل الملاحظة مجانًا عند إبلاغنا خلال 24 ساعة من التسليم.'],
    ];

    public function up(): void
    {
        foreach (self::TITLES as $slug => [$old, $new]) {
            $this->swapTitle($slug, $old, $new);
        }

        foreach (self::TEXT as [$old, $new]) {
            $this->replaceText($old, $new);
        }

        $pageId = DB::table('pages')->where('slug', self::AREA_SLUG)->where('pageable_type', 'area')->value('id');

        if ($pageId === null || DB::table('faqs')->where('page_id', $pageId)->exists()) {
            return;
        }

        foreach (self::AREA_FAQS as $i => [$question, $answer]) {
            DB::table('faqs')->insert(['page_id' => $pageId, 'question' => $question, 'answer' => $answer, 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        if (! DB::table('content_blocks')->where('page_id', $pageId)->where('type', 'faq')->exists()) {
            DB::table('content_blocks')->insert([
                'page_id' => $pageId,
                'type' => 'faq',
                'position' => (int) DB::table('content_blocks')->where('page_id', $pageId)->max('position') + 1,
                'is_active' => true,
                'data' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::TITLES as $slug => [$old, $new]) {
            $this->swapTitle($slug, $new, $old);
        }

        foreach (self::TEXT as [$old, $new]) {
            $this->replaceText($new, $old);
        }

        $pageId = DB::table('pages')->where('slug', self::AREA_SLUG)->where('pageable_type', 'area')->value('id');

        if ($pageId !== null) {
            DB::table('faqs')->where('page_id', $pageId)->whereIn('question', array_column(self::AREA_FAQS, 0))->delete();
            DB::table('content_blocks')->where('page_id', $pageId)->where('type', 'faq')->delete();
        }
    }

    private function swapTitle(string $slug, string $from, string $to): void
    {
        $pageIds = DB::table('pages')->where('slug', $slug)->pluck('id');

        DB::table('seo_metadata')->whereIn('page_id', $pageIds)->where('meta_title', $from)->update(['meta_title' => $to, 'updated_at' => now()]);
    }

    private function replaceText(string $search, string $replace): void
    {
        foreach (DB::table('content_blocks')->where('type', 'rich_text')->get() as $block) {
            $data = json_decode($block->data, true);
            $content = (string) ($data['content'] ?? '');

            if (str_contains($content, $search)) {
                $data['content'] = str_replace($search, $replace, $content);
                DB::table('content_blocks')->where('id', $block->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
            }
        }
    }
};
