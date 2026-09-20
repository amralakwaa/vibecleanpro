<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use Illuminate\Database\Seeder;

/**
 * The company data already approved in the project documents (NAP used
 * across every page draft and schema). Only fills fields that are still
 * empty - anything an editor has entered in the admin panel wins, so this
 * is safe to re-run. Unconfirmed data (email, address, hours, founder) is
 * deliberately left empty: an empty field is hidden on the public site,
 * a guessed one would be published.
 */
class CompanyProfileSeeder extends Seeder
{
    /**
     * Owner-confirmed values. `working_hours` keeps 24-hour times so the
     * structured data generator can read them unambiguously; the label is
     * what visitors see. The lead notification inbox is deliberately NOT
     * here - it is an internal address the owner decides separately.
     *
     * @var array<string, string|array<string, string>>
     */
    public const APPROVED = [
        'name' => 'Vibe Clean Pro',
        'phone' => '+966534999194',
        'whatsapp_number' => '966534999194',
        'city' => 'الرياض',
        'email' => 'info@vibecleanpro.com',
        'service_area' => 'مدينة الرياض',
        'working_hours' => ['كل أيام الأسبوع' => 'من 08:00 إلى 14:00'],

        // Trust points the owner confirmed on 2026-09-20. Wording states
        // the commitment without naming a certificate or an issuing body -
        // those come from the owner and are added in the panel.
        'trust_points' => [
            ['title' => 'فريق سعودي مدرب', 'description' => 'الفريق الذي يصل إليك هو فريقنا، مدرب على تنفيذ كل خدمة بمعاييرها.', 'icon' => 'users'],
            ['title' => 'شهادات اعتماد', 'description' => 'نعمل وفق معايير جودة واضحة، ولدينا شهادات اعتماد على أعمالنا.', 'icon' => 'shield-check'],
            ['title' => 'ضمان يصل إلى 10 سنوات', 'description' => 'نقدم ضمانًا يصل إلى 10 سنوات حسب نوع الخدمة ونطاق التنفيذ وشروط الضمان.', 'icon' => 'check-circle'],
            ['title' => 'عرض سعر مكتوب قبل التنفيذ', 'description' => 'نطاق العمل والسعر يصلانك مكتوبين قبل أن يبدأ الفريق.', 'icon' => 'sparkles'],
        ],

        // Identity and founder. The founder's name is the one recorded in
        // the project's own research and architecture documents; the rest
        // is the company's positioning in its own voice - no founding
        // year, no years of experience, no customer counts, because none
        // of those is established.
        'tagline' => 'تنظيف موثَّق في الرياض',
        'identity_statement' => 'شركة تنظيف في الرياض تعمل بفريق سعودي مدرب، توثّق أعمالها بالصور، وترسل نطاق العمل وسعره مكتوبين قبل أن يبدأ الفريق.',
        'story' => '<p>بدأت فايب كلين برو من ملاحظة يعرفها كل من طلب خدمة تنظيف في الرياض: سعر يُقال على الهاتف ثم يتغيّر عند الباب، وصور لامعة على المواقع لا تمتّ للفريق الذي سيصل بصلة، ونطاق عمل لا أحد يعرف حدوده إلا بعد أن يبدأ الخلاف.</p><p>اخترنا أن نعمل بالعكس تمامًا. نفهم المكان أولًا بالصور أو بالمعاينة، ثم نرسل نطاق العمل وسعره مكتوبين، ولا نبدأ قبل أن تعرف ما سيُنفَّذ وما لن يُنفَّذ. وننشر صور أعمالنا نحن — من مواقع نفّذ فيها فريقنا العمل فعلًا — لا صورًا مشتراة من بنوك الصور.</p><p>ولأن العمل الجيد يجب أن يبقى بعد مغادرة الفريق، نجري جولة تسليم معك قبل الخروج، ونسند أعمالنا بضمان مكتوب تُذكر مدته في عرض السعر.</p>',
        'mission' => 'أن يعرف كل عميل في الرياض ما الذي سيُنفَّذ في مكانه وكم سيكلّفه، مكتوبًا وقبل أن يبدأ العمل.',
        'vision' => 'أن تكون فايب كلين برو المرجع الذي يُقاس عليه وضوح خدمات التنظيف في الرياض وجودة توثيقها.',
        'values' => [
            ['title' => 'الوضوح قبل السعر', 'description' => 'نطاق العمل يُكتب قبل الرقم، وما لا تشمله الخدمة يُقال قبل البدء لا بعده.'],
            ['title' => 'الدليل لا الوعد', 'description' => 'ما نقوله عن أنفسنا مسنود بصورة من موقع عمل حقيقي أو بالتزام مكتوب في عرض السعر.'],
            ['title' => 'احترام المكان', 'description' => 'نتعامل مع كل سطح بمادته الصحيحة، ونغطّي ما يحتاج تغطية، ونترك المكان كما نحب أن نجده.'],
            ['title' => 'فريق يمثّلنا', 'description' => 'من يصل إليك هو فريقنا المدرب بزيّ موحد، لا عمالة تُستأجر لليوم الواحد.'],
            ['title' => 'الالتزام بما يُكتب', 'description' => 'ما ورد في عرض السعر هو المرجع عند أي خلاف، ومدة الضمان تُذكر فيه صراحة.'],
        ],
        'founder_name' => 'المهندس عمر بلال الأكوع',
        'founder_title' => 'مؤسس فايب كلين برو',
        'founder_bio' => 'مؤسس فايب كلين برو، ويضع معايير العمل التي تسير عليها الفرق: نطاق مكتوب قبل البدء، وتوثيق مصوَّر لكل مشروع، وجولة تسليم مع العميل قبل مغادرة الموقع.',
        'founder_long_bio' => '<p>أسّس عمر الأكوع فايب كلين برو على قاعدة واحدة: أن يكون كل ما يُقال للعميل قابلًا للتحقق. من هنا جاء إصرار الشركة على عرض السعر المكتوب قبل التنفيذ، وعلى نشر صور أعمالها هي لا صور بنوك الصور، وعلى جولة تسليم تُنهى فيها الملاحظات في حينها.</p><p>ويتابع شخصيًا معايير التنفيذ: اختيار المادة المناسبة لكل سطح، وطريقة الوصول الآمن في أعمال الارتفاعات، وما يُسمح بنشره من صور المواقع حفاظًا على خصوصية العملاء.</p>',
    ];

    public function run(): void
    {
        // The site has exactly one profile, whatever its id - never create a
        // second one next to an existing row.
        $profile = BusinessProfile::query()->first() ?? new BusinessProfile;

        foreach (self::APPROVED as $field => $value) {
            if (blank($profile->{$field})) {
                $profile->{$field} = $value;
            }
        }

        $profile->save();
    }
}
