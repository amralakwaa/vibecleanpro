<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sitewide FAQs (page_id = null) shown on the homepage. Every answer restates
 * a fact already published elsewhere on the site: pricing method (/blog/
 * cleaning-prices-riyadh, «كيف نسعّر»), coverage (/areas), the 24-hour
 * re-clean (/warranty), quote steps (cleaning-prices FAQ), and the team
 * (business values). Editable afterwards in the admin FAQ resource.
 */
return new class extends Migration
{
    /** @var list<array{0: string, 1: string}> */
    private const FAQS = [
        ['كم سعر خدمات التنظيف في الرياض؟', 'يختلف السعر حسب الخدمة والمساحة وحالة المكان ونطاق العمل. نرسل لك عرض سعر مكتوبًا يوضح ما سيُنفَّذ بعد صور المكان أو معاينته، قبل أن يبدأ الفريق، ولكل خدمة سعر يبدأ منه تجده في صفحتها وفي صفحة الأسعار.'],
        ['ما الأحياء التي تخدمونها في الرياض؟', 'نعمل في أحياء شمال الرياض وشرقها ووسطها، ونضيف الأحياء تباعًا حين يكون لنا فيها عمل فعلي. القائمة الكاملة بالأحياء في صفحة مناطق التغطية.'],
        ['ما الضمان على خدمة التنظيف؟', 'إذا لم تطابق النتيجة نطاق العمل المتفق عليه، نعيد تنظيف الجزء محل الملاحظة مجانًا عند إبلاغنا خلال 24 ساعة من التسليم. التفاصيل وما لا يشمله الضمان في صفحة الضمان.'],
        ['كيف أطلب عرض سعر؟', 'أرسل صورًا واضحة للمكان ووصفًا لما تحتاجه عبر واتساب أو نموذج طلب عرض السعر، وفي الأماكن الكبيرة أو الواجهات المرتفعة نرتب معاينة. يصلك نطاق العمل والسعر مكتوبين، ولا يتم الدفع عبر الموقع.'],
        ['هل الفريق الذي يصل إليّ تابع لكم؟', 'نعم، الفريق الذي يصل إليك هو فريقنا المدرب بزي موحد، وليس عمالة تُستأجر لليوم الواحد.'],
    ];

    public function up(): void
    {
        $nextOrder = (int) DB::table('faqs')->whereNull('page_id')->max('sort_order');

        foreach (self::FAQS as [$question, $answer]) {
            if (DB::table('faqs')->whereNull('page_id')->where('question', $question)->exists()) {
                continue;
            }

            DB::table('faqs')->insert([
                'page_id' => null,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => ++$nextOrder,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('faqs')->whereNull('page_id')->whereIn('question', array_column(self::FAQS, 0))->delete();
    }
};
