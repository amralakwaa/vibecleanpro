<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes A22 (water-tank-cleaning-guide-riyadh): the first migration run failed
 * after inserting the page but before inserting FAQs and service links.
 * The idempotency check skipped it on the second run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pageId = DB::table('pages')->where('slug', 'water-tank-cleaning-guide-riyadh')->value('id');
        $articleId = DB::table('pages')->where('slug', 'water-tank-cleaning-guide-riyadh')->value('pageable_id');

        if (! $pageId) {
            return;
        }

        if (DB::table('faqs')->where('page_id', $pageId)->doesntExist()) {
            $now = now()->toDateTimeString();

            $faqs = [
                ['كم تكلفة تنظيف خزان المياه بالرياض؟', 'السعر يعتمد على حجم الخزان (عدد اللترات أو الجالونات) وموقعه (أرضي/علوي/مدفون) ونوع الخدمة (تنظيف فقط أم تعقيم كامل). أرسل لنا المواصفات وستصلك نبذة سعرية خلال دقائق.'],
                ['هل تنظيف الخزان يتطلب قطع المياه عن المنزل؟', 'نعم، يتطلب قطع الإمداد خلال فترة التنظيف والتعقيم (عادةً 3-5 ساعات). ننسق معك الوقت المناسب لتقليل الإزعاج.'],
                ['هل يمكن تنظيف الخزان الأرضي والمدفون؟', 'نعم. الخزانات الأرضية والمدفونة من أكثر ما يحتاج تنظيفاً منتظماً لأن درجات الحرارة والرطوبة فيها تشجع نمو الطحالب. فريقنا مجهز للدخول الآمن والتنظيف الداخلي.'],
                ['كم يستغرق تنظيف الخزان؟', 'خزان عادي (5000-10000 لتر) يستغرق 2-4 ساعات. الخزانات الكبيرة جداً أو شديدة التلوث قد تأخذ وقتاً أطول. نحدد الوقت التقديري عند المعاينة.'],
                ['هل تقدمون تقريراً بعد تنظيف الخزان؟', 'نعم، نقدم تقريراً مكتوباً يوضح الحالة التي وجدنا عليها الخزان والإجراءات التي نفذناها والمواد المستخدمة. يفيدك في متابعة دورية التنظيف القادمة.'],
            ];

            foreach ($faqs as $i => $faq) {
                DB::table('faqs')->insert([
                    'page_id'    => $pageId,
                    'question'   => $faq[0],
                    'answer'     => $faq[1],
                    'sort_order' => $i + 1,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($articleId && DB::table('article_service')->where('article_id', $articleId)->doesntExist()) {
            $now = now()->toDateTimeString();

            foreach ([11, 15, 1] as $serviceId) {
                DB::table('article_service')->insert([
                    'article_id' => $articleId,
                    'service_id' => $serviceId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $pageId = DB::table('pages')->where('slug', 'water-tank-cleaning-guide-riyadh')->value('id');

        if ($pageId) {
            DB::table('faqs')->where('page_id', $pageId)->delete();
        }
    }
};
