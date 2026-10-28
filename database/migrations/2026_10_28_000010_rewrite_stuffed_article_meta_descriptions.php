<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Only rows still carrying the stuffed "افضل/ارخص" template are touched,
     * so a description already rewritten in the admin panel is never overwritten.
     */
    public function up(): void
    {
        $descriptions = [
            'cleaning-prices-riyadh' => 'أسعار شركات التنظيف بالرياض: ما الذي يحدد السعر لكل خدمة — المساحة وحالة المكان ونطاق العمل والوصول — وكيف تقارن بين عرضَي سعر مكتوبين قبل أن تختار.',
            'how-to-choose-cleaning-company-riyadh' => 'كيف تختار شركة تنظيف بالرياض؟ 7 معايير تتحقق منها قبل الحجز: كيان نظامي، عرض سعر مكتوب، صور أعمال حقيقية، إشراف على الفريق، وطريقة تسليم ومراجعة واضحة.',
            'post-construction-handover-checklist' => 'قائمة تحقق استلام العقار بعد التشطيب بالرياض: ماذا تفحص في الزجاج والأرضيات والمطبخ ودورات المياه والتكييف، وكيف تتأكد من إزالة بقايا الجص والدهان قبل التوقيع.',
            'villa-cleaning-cost-riyadh' => 'كم تكلفة تنظيف فيلا بالرياض؟ العوامل التي تحدد السعر: عدد الأدوار والمساحة، وحالة الفيلا، ومفروشة أم فارغة، والأحواش والزجاج المرتفع، وكيف تحصل على عرض دقيق.',
            'villa-cleaning-checklist' => 'ماذا يشمل التنظيف الشامل للفيلا بالرياض؟ قائمة دورًا بدور من السطح والملحق إلى الغرف والدرج والمطبخ والأحواش، مع ما لا يدخل عادة في الخدمة وما تفحصه عند التسليم.',
            'facade-cleaning-cost-riyadh' => 'سعر تنظيف الواجهات في الرياض: كيف يُحسب بالمتر المربع، وأثر الارتفاع وخامة الواجهة وطريقة الوصول ودرجة الاتساخ، وما يجب أن يتضمنه عرض السعر المكتوب.',
            'facade-access-rope-lift-scaffold' => 'طرق الوصول لتنظيف واجهات المباني بالرياض: أعمدة تلسكوبية أم رافعة سلة أم حبال أم سقالة؟ متى تناسب كل طريقة حسب الارتفاع وشكل المبنى، ومتطلبات السلامة لكل منها.',
            'streak-free-glass-cleaning-riyadh' => 'زجاج المبنى بالرياض بلا خطوط: لماذا تظهر البقع البيضاء بعد التنظيف، وما المواد والأدوات التي تخدش الزجاج، وكيف تحافظ على وضوحه بين التنظيفات وكم مرة يُنظَّف.',
            'office-cleaning-scope-of-work' => 'نطاق عمل تنظيف المكاتب بالرياض: ما يجب أن يحدده الاتفاق — المهام اليومية والأسبوعية والشهرية، وأوقات العمل، والمستهلكات، والإشراف — لتتجنب الخلاف لاحقًا.',
            'courtyard-interlock-cleaning' => 'تنظيف الأحواش والانترلوك بالرياض: لماذا يسحب الضغط الزائد رمل الفواصل، والطريقة الصحيحة لإزالة بقع الزيت والترسبات البيضاء والأعشاب دون إتلاف الأرضية.',
            'sofa-majlis-cleaning-steam-or-wash-riyadh' => 'تنظيف الكنب والمجالس بالرياض: متى يناسب البخار ومتى يناسب الغسيل والاستخلاص أو التنظيف الجاف للأقمشة الحساسة، مع معالجة البقع والروائح ومدة التجفيف المتوقعة.',
            'ac-cleaning-when-why-riyadh' => 'تنظيف المكيفات بالرياض: علامات أن مكيفك يحتاج تنظيفًا، وما الذي يُنظَّف فعلًا في السبليت والشباك والمركزي، وكل كم مرة يحتاج التنظيف في أجواء الرياض المغبرة.',
            'water-tank-cleaning-how-often-riyadh' => 'كل كم مرة تحتاج تنظيف وتعقيم خزان المياه بالرياض؟ لماذا تتراكم الرواسب، والعلامات التي تدل على أن الوقت قد حان، وما الذي يخرج عن نطاق الخدمة.',
            'sofa-majlis-cleaning-cost-riyadh' => 'كم تكلفة تنظيف الكنب والمجالس بالرياض؟ العوامل التي تحدد السعر: عدد القطع والمقاعد، ونوع القماش، وطريقة التنظيف، ودرجة البقع، وكيف تحصل على عرض سعر دقيق.',
            'pest-control-treatment-vs-prevention-riyadh' => 'مكافحة الحشرات بالرياض: الفرق بين المعالجة الموضعية لإصابة قائمة والوقاية الدورية لمنع المشكلة، والحشرات الشائعة في المنازل، ودورك في السلامة أثناء الخدمة.',
            'apartment-move-in-out-cleaning-riyadh' => 'تنظيف الشقة قبل الانتقال وبعد الإخلاء بالرياض: قائمة عملية لكل حالة، والفرق بينهما، وما لا يدخل عادة في الخدمة، وما تراجعه عند التسليم لتستلم أو تسلّم شقة نظيفة.',
            'marble-polishing-when-why-riyadh' => 'جلي وتلميع الرخام بالرياض: ما الفرق بين الجلي والتلميع، والعلامات التي تدل على أن رخامك يحتاج معالجة، وكيف تتم العملية على مراحل، وما الذي لا تصلحه.',
            'disinfection-when-needed-riyadh' => 'التعقيم والتطهير بالرياض: الفرق بين التنظيف والتطهير والتعقيم، ولماذا يأتي التنظيف أولًا، ومتى تحتاج تطهيرًا فعلًا، والنقاط كثيرة اللمس، وحدود الخدمة الآمنة.',
        ];

        foreach ($descriptions as $slug => $description) {
            $page = DB::table('pages')
                ->where('slug', $slug)
                ->where('pageable_type', 'article')
                ->first();

            if (! $page) {
                continue;
            }

            $seo = DB::table('seo_metadata')->where('page_id', $page->id)->first();

            if (! $seo || ! $this->isStuffed($seo->meta_description)) {
                continue;
            }

            $update = ['meta_description' => $description, 'updated_at' => now()];

            if ($this->isStuffed($seo->og_description)) {
                $update['og_description'] = $description;
            }

            DB::table('seo_metadata')->where('id', $seo->id)->update($update);
        }
    }

    public function down(): void
    {
        //
    }

    private function isStuffed(?string $text): bool
    {
        return $text !== null && (str_contains($text, 'ارخص') || str_contains($text, 'افضل'));
    }
};
