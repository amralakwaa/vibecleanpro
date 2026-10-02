<?php

namespace Database\Seeders;

use App\Enums\CredentialStatus;
use App\Enums\CredentialType;
use App\Models\Credential;
use Illuminate\Database\Seeder;

/**
 * The trust/credentials data: ten internal Vibe Clean Pro operating standards
 * (real, adopted, public), plus the admin-only roadmap of external ISO
 * certifications and Saudi licences recorded as Planned/Pending.
 *
 * Honesty: internal standards are clearly labelled "معيار تشغيلي داخلي" and
 * never dressed up as ISO or a government accreditation. External credentials
 * carry NO invented number, body or date - they sit at Planned/Pending until a
 * real document is entered, and scopePublic keeps them out of every public view
 * until Verified. Idempotent (updateOrCreate by slug) so it also updates the
 * current DB and reproduces cleanly on a fresh seed.
 */
class CredentialSeeder extends Seeder
{
    private const ISSUED = '2026-09-01';

    private const REVIEW = '2027-09-01';

    public function run(): void
    {
        foreach ($this->internalStandards() as $order => $standard) {
            $this->write(array_merge($standard, [
                'credential_type' => CredentialType::InternalStandard,
                'status' => CredentialStatus::Active,
                'issuer' => 'Vibe Clean Pro',
                'version' => '1.0',
                'issued_at' => self::ISSUED,
                'review_at' => self::REVIEW,
                'is_internal' => true,
                'is_public' => true,
                'sort_order' => $order + 1,
            ]));
        }

        foreach ($this->roadmap() as $order => $row) {
            $this->write(array_merge($row, [
                'is_internal' => false,
                'is_public' => false,
                'sort_order' => 100 + $order,
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function write(array $data): void
    {
        Credential::query()->updateOrCreate(['slug' => $data['slug']], $data);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function internalStandards(): array
    {
        return [
            [
                'slug' => 'quality-management-standard',
                'document_code' => 'VCP-QMS-001',
                'icon' => 'badge-check',
                'name_ar' => 'معيار إدارة الجودة',
                'name_en' => 'Quality Management Standard',
                'summary_ar' => 'حلقة الجودة من المعاينة إلى إغلاق الخدمة وتوثيقها، لضمان نتيجة ثابتة في كل زيارة.',
                'scope' => 'كل خدمة تنفّذها فايب كلين برو، سكنية أو تجارية.',
                'body_ar' => $this->body(
                    'يحدّد هذا المعيار كيف تُدار جودة كل خدمة من لحظة الطلب حتى الإغلاق، بحيث تكون النتيجة ثابتة لا تعتمد على اجتهاد فردي.',
                    'يسري على جميع الخدمات والفرق في كل المواقع.',
                    ['<li>مسؤول الخدمة: تطبيق الخطوات والتوثيق.</li>', '<li>قائد الفريق: مطابقة التنفيذ للنطاق المكتوب.</li>', '<li>خدمة العملاء: جولة المراجعة وإغلاق الملاحظات.</li>'],
                    ['<li>المعاينة وفهم حالة المكان.</li>', '<li>كتابة نطاق العمل وما لا يشمله قبل البدء.</li>', '<li>توزيع المهام على الفريق.</li>', '<li>التنفيذ وفق خطوات كل نوع سطح.</li>', '<li>مراجعة النتيجة مع العميل (جولة تسليم).</li>', '<li>معالجة الملاحظات في حينها.</li>', '<li>إغلاق الخدمة بعد الرضا عن النطاق.</li>', '<li>توثيق المشروع حسب <a href="/trust/verify/VCP-PDS-001">معيار التوثيق</a>.</li>'],
                    ['<li>لا يُعدّ العمل منتهيًا قبل جولة المراجعة.</li>', '<li>عرض السعر ونطاق العمل مكتوبان دائمًا قبل التنفيذ.</li>'],
                    ['<a href="/trust/verify/VCP-QCI-001">معيار فحص الجودة قبل التسليم</a>', '<a href="/warranty">الضمان وشروط الخدمة</a>'],
                ),
            ],
            [
                'slug' => 'safety-occupational-health-standard',
                'document_code' => 'VCP-SHS-001',
                'icon' => 'shield-check',
                'name_ar' => 'معيار السلامة والصحة المهنية',
                'name_en' => 'Safety & Occupational Health Standard',
                'summary_ar' => 'تقييم الموقع وتنظيمه وحماية الناس والممتلكات، مع إجراءات خاصة لأعمال الارتفاع.',
                'scope' => 'كل عمل ميداني، وبالأخص الواجهات والزجاج المرتفع.',
                'body_ar' => $this->body(
                    'يضع هذا المعيار قواعد السلامة الواجب اتباعها أثناء التنفيذ، لحماية الفريق والعميل والممتلكات.',
                    'جميع الأعمال الميدانية؛ وتزداد صرامته في أعمال الارتفاع.',
                    ['<li>قائد الفريق: تقييم مخاطر الموقع قبل البدء.</li>', '<li>كل فرد: التزام إجراءات السلامة ومعدات الحماية.</li>'],
                    ['<li>تقييم الموقع وتحديد المخاطر.</li>', '<li>تنظيم منطقة العمل وتأمينها.</li>', '<li>حماية العميل والممتلكات والأسطح المجاورة.</li>', '<li>استخدام الأدوات والمواد بطريقة آمنة ومناسبة.</li>', '<li>معدات وصول مناسبة لأعمال الارتفاع وإجراءات احترازية.</li>', '<li>الإبلاغ الفوري عن أي خطر.</li>', '<li>مراجعة الموقع بعد التنفيذ.</li>'],
                    ['<li>أعمال الارتفاع لا تبدأ قبل تأمين الوصول.</li>', '<li>يُوقف العمل عند أي خطر غير مؤمَّن.</li>'],
                    ['<a href="/trust/verify/VCP-PPS-001">معيار حماية الممتلكات</a>', '<a href="/trust/verify/VCP-QMS-001">معيار إدارة الجودة</a>'],
                ),
            ],
            [
                'slug' => 'customer-privacy-standard',
                'document_code' => 'VCP-PRV-001',
                'icon' => 'lock',
                'name_ar' => 'معيار خصوصية بيانات العملاء',
                'name_en' => 'Customer Privacy Standard',
                'summary_ar' => 'كيف نتعامل مع بيانات العميل وموقعه وصوره، وما يُنشر وما لا يُنشر.',
                'scope' => 'بيانات العملاء، والتصوير، ونشر المشاريع.',
                'body_ar' => $this->body(
                    'يحدّد هذا المعيار كيف تُجمع بيانات العميل وتُحفظ وتُستخدم، وكيف تُحمى خصوصيته في التصوير والنشر.',
                    'الهاتف والموقع والصور والفيديو والمستندات وبيئة المكاتب وشاشات الموظفين.',
                    ['<li>كل فرد في الفريق: احترام خصوصية المكان وما فيه.</li>', '<li>مسؤول النشر: مراجعة كل صورة قبل نشرها.</li>'],
                    ['<li>جمع أقل قدر من البيانات اللازم للخدمة.</li>', '<li>عدم تصوير ما يكشف هوية عميل أو عامل دون إذن.</li>', '<li>استبعاد المستندات وشاشات الموظفين من التصوير.</li>', '<li>مراجعة الصور قبل النشر وفق معيار التوثيق.</li>', '<li>عدم مشاركة بيانات العميل مع طرف لا علاقة له بالخدمة.</li>'],
                    ['<li>الصور التي تكشف خصوصية لا تُنشر إطلاقًا.</li>'],
                    ['<a href="/privacy">سياسة الخصوصية</a>', '<a href="/trust/verify/VCP-PDS-001">معيار توثيق المشاريع والصور</a>'],
                ),
            ],
            [
                'slug' => 'environmental-responsibility-standard',
                'document_code' => 'VCP-ENV-001',
                'icon' => 'sparkles',
                'name_ar' => 'معيار المسؤولية البيئية',
                'name_en' => 'Environmental Responsibility Standard',
                'summary_ar' => 'استخدام منضبط للمواد والمياه، واختيار الطريقة المناسبة، والتخلص السليم من المخلفات.',
                'scope' => 'استهلاك المواد والمياه وإدارة المخلفات في كل خدمة.',
                'body_ar' => $this->body(
                    'يوجّه هذا المعيار نحو تنفيذ مسؤول بيئيًا دون المساس بجودة النتيجة.',
                    'كل خدمة تستهلك مواد أو مياه أو تنتج مخلفات.',
                    ['<li>قائد الفريق: ضبط كميات المواد والمياه.</li>', '<li>كل فرد: التخلص السليم من المخلفات.</li>'],
                    ['<li>استخدام المواد بالقدر الكافي للنتيجة دون إفراط.</li>', '<li>ضبط استهلاك المياه وتقليل الهدر.</li>', '<li>اختيار الطريقة والمواد المناسبة لكل سطح.</li>', '<li>جمع المخلفات والتخلص منها بشكل سليم.</li>'],
                    ['<li>لا يُضحّى بجودة النتيجة، بل يُمنع الهدر غير الضروري.</li>'],
                    ['<a href="/trust/verify/VCP-QMS-001">معيار إدارة الجودة</a>'],
                ),
            ],
            [
                'slug' => 'team-training-standard',
                'document_code' => 'VCP-TRN-001',
                'icon' => 'badge-check',
                'name_ar' => 'معيار تدريب فريق العمل',
                'name_en' => 'Team Training Standard',
                'summary_ar' => 'برنامج تدريب داخلي يغطي السلامة والمعدات والأسطح والخصوصية وخدمة العملاء.',
                'scope' => 'كل عضو في الفريق الميداني قبل العمل المستقل وأثناءه.',
                'body_ar' => $this->body(
                    'يحدّد هذا المعيار ما يتدرّب عليه الفريق داخليًا قبل أن يعمل باستقلالية، وكيف تُسجَّل هذه التدريبات.',
                    'السلامة المهنية، استخدام المعدات، التعامل مع الأسطح، العمل في المواقع التجارية، خصوصية العميل، خدمة العملاء، التعقيم، ومعدات الارتفاع عند الحاجة.',
                    ['<li>مشرف التدريب: تنفيذ البرنامج وتسجيله.</li>', '<li>العضو: اجتياز التدريب قبل العمل المستقل.</li>'],
                    ['<li>تدريب تمهيدي على السلامة والمعدات.</li>', '<li>تدريب على التعامل مع أنواع الأسطح.</li>', '<li>تدريب على خصوصية العميل وخدمته.</li>', '<li>تدريب خاص بأعمال الارتفاع عند الحاجة.</li>', '<li>تسجيل كل تدريب: التاريخ والمدرّب والنطاق.</li>'],
                    ['<li>هذا تدريب داخلي من فايب كلين برو، وليس شهادة حكومية أو خارجية.</li>'],
                    ['<a href="/trust/verify/VCP-SHS-001">معيار السلامة والصحة المهنية</a>'],
                ),
            ],
            [
                'slug' => 'customer-care-resolution-charter',
                'document_code' => 'VCP-CCR-001',
                'icon' => 'inbox',
                'name_ar' => 'ميثاق خدمة العملاء ومعالجة الملاحظات',
                'name_en' => 'Customer Care & Resolution Charter',
                'summary_ar' => 'رحلة العميل من استلام الطلب إلى المتابعة بعد الخدمة، ونقطة تواصل واضحة للملاحظات.',
                'scope' => 'كل طلب خدمة ومتابعته.',
                'body_ar' => $this->body(
                    'يحدّد هذا الميثاق كيف نتعامل مع العميل في كل مرحلة، وكيف نستقبل ملاحظاته ونعالجها.',
                    'جميع الطلبات وقنوات التواصل.',
                    ['<li>خدمة العملاء: التواصل والمتابعة وإغلاق الطلب.</li>'],
                    ['<li>استلام الطلب وتأكيد نطاق الخدمة.</li>', '<li>تواصل واضح قبل الموعد وأثناءه.</li>', '<li>استقبال الملاحظات ومعالجتها.</li>', '<li>تصعيد ما يحتاج تصعيدًا حسب معيار الشكاوى.</li>', '<li>إغلاق الطلب بعد الرضا.</li>', '<li>متابعة ما بعد الخدمة عند الحاجة.</li>'],
                    ['<li>لا يُغلق الطلب قبل معالجة الملاحظات المتفق على أنها ضمن النطاق.</li>'],
                    ['<a href="/complaints">الشكاوى والتعويضات</a>', '<a href="/trust/verify/VCP-CES-001">معيار إدارة الشكاوى والتصعيد</a>'],
                ),
            ],
            [
                'slug' => 'service-inspection-standard',
                'document_code' => 'VCP-QCI-001',
                'icon' => 'clipboard',
                'name_ar' => 'معيار فحص جودة الخدمة قبل التسليم',
                'name_en' => 'Service Inspection Standard',
                'summary_ar' => 'قائمة فحص وجولة تسليم مع العميل قبل مغادرة الفريق.',
                'scope' => 'كل خدمة قبل اعتبارها منتهية.',
                'body_ar' => $this->body(
                    'يحدّد هذا المعيار كيف تُفحص النتيجة قبل التسليم، لضمان مطابقتها للنطاق المتفق عليه.',
                    'كل خدمة قبل الإغلاق.',
                    ['<li>قائد الفريق: الفحص الداخلي.</li>', '<li>خدمة العملاء/العميل: جولة التسليم.</li>'],
                    ['<li>فحص داخلي مقابل نطاق العمل المكتوب.</li>', '<li>جولة تسليم مع العميل على النقاط الرئيسية.</li>', '<li>تسجيل أي ملاحظة ومعالجتها قبل المغادرة.</li>'],
                    ['<li>لا تسليم دون جولة مراجعة.</li>'],
                    ['<a href="/trust/verify/VCP-QMS-001">معيار إدارة الجودة</a>'],
                ),
            ],
            [
                'slug' => 'complaints-escalation-standard',
                'document_code' => 'VCP-CES-001',
                'icon' => 'scale',
                'name_ar' => 'معيار إدارة الشكاوى والتصعيد',
                'name_en' => 'Complaints & Escalation Standard',
                'summary_ar' => 'استقبال الشكوى وتصنيفها وإسنادها ومعالجتها وإغلاقها مع سجل تدقيق.',
                'scope' => 'كل شكوى أو ملاحظة ترد من عميل.',
                'body_ar' => $this->body(
                    'يحدّد هذا المعيار مسار الشكوى من استقبالها حتى إغلاقها، بما يضمن عدم ضياع أي ملاحظة.',
                    'جميع الشكاوى عبر كل القنوات.',
                    ['<li>خدمة العملاء: الاستقبال والتصنيف والإسناد والإغلاق.</li>'],
                    ['<li>استقبال الشكوى وتسجيلها برقم.</li>', '<li>تصنيفها ووصفها وإرفاق ما يلزم.</li>', '<li>إسنادها للمسؤول المناسب.</li>', '<li>تحديد الحالة ومعالجتها.</li>', '<li>الإغلاق مع توثيق النتيجة وسجل التدقيق.</li>'],
                    ['<li>لكل شكوى رقم وحالة حتى الإغلاق.</li>'],
                    ['<a href="/complaints">الشكاوى والتعويضات</a>', '<a href="/trust/verify/VCP-CCR-001">ميثاق خدمة العملاء</a>'],
                ),
            ],
            [
                'slug' => 'property-protection-standard',
                'document_code' => 'VCP-PPS-001',
                'icon' => 'shield-check',
                'name_ar' => 'معيار حماية الممتلكات أثناء العمل',
                'name_en' => 'Property Protection Standard',
                'summary_ar' => 'حماية الأثاث والأرضيات والمداخل والأجهزة والأسطح الحساسة، مع فحص قبل وبعد.',
                'scope' => 'كل موقع عمل وما فيه من ممتلكات.',
                'body_ar' => $this->body(
                    'يحدّد هذا المعيار كيف تُحمى ممتلكات العميل أثناء التنفيذ، وكيف يُوثَّق وضعها قبل العمل وبعده.',
                    'الأثاث والأرضيات والمداخل والنباتات والأجهزة والزجاج والأسطح الحساسة.',
                    ['<li>قائد الفريق: تأمين الممتلكات قبل البدء.</li>'],
                    ['<li>فحص المكان وتحديد ما يحتاج حماية.</li>', '<li>تغطية وحماية الأثاث والأرضيات والمداخل.</li>', '<li>العناية بالأجهزة والأسطح الحساسة والنباتات.</li>', '<li>فحص بعد التنفيذ والتأكد من سلامة الممتلكات.</li>'],
                    ['<li>أي ملاحظة على ممتلكات تُبلَّغ فورًا.</li>'],
                    ['<a href="/trust/verify/VCP-SHS-001">معيار السلامة والصحة المهنية</a>'],
                ),
            ],
            [
                'slug' => 'project-documentation-standard',
                'document_code' => 'VCP-PDS-001',
                'icon' => 'clipboard',
                'name_ar' => 'معيار توثيق المشاريع والصور',
                'name_en' => 'Project Documentation Standard',
                'summary_ar' => 'توثيق قبل/أثناء/بعد مع فحص خصوصية واعتماد الوسائط قبل النشر.',
                'scope' => 'كل مشروع يُوثَّق أو يُنشر على الموقع.',
                'body_ar' => $this->body(
                    'يحدّد هذا المعيار كيف تُوثَّق الأعمال بالصور والفيديو، وكيف تُراجَع الخصوصية قبل النشر.',
                    'التقاط الوسائط وتسميتها وربطها بالمشاريع ونشرها وأرشفتها.',
                    ['<li>الفريق: التقاط قبل/أثناء/بعد.</li>', '<li>مسؤول النشر: فحص الخصوصية واعتماد الوسائط.</li>'],
                    ['<li>توثيق مراحل العمل: قبل وأثناء وبعد.</li>', '<li>فحص الخصوصية واستبعاد ما يكشف هوية.</li>', '<li>اعتماد الوسائط قبل النشر.</li>', '<li>تسمية الملفات وربطها بالمشروع.</li>', '<li>أرشفة الوسائط.</li>'],
                    ['<li>لا يُنشر إلا ما اجتاز فحص الخصوصية والاعتماد.</li>'],
                    ['<a href="/trust/verify/VCP-PRV-001">معيار خصوصية بيانات العملاء</a>', '<a href="/projects">أعمالنا</a>'],
                ),
            ],
        ];
    }

    /**
     * Admin-only roadmap: external ISO certifications and Saudi licences the
     * company intends to pursue. Recorded WITHOUT any invented number, body or
     * date, at Planned/Pending, is_public = false - never shown as held.
     *
     * @return list<array<string, mixed>>
     */
    private function roadmap(): array
    {
        $iso = fn (string $slug, string $code, string $ar, string $en, string $summary) => [
            'slug' => $slug,
            'credential_type' => CredentialType::Certification,
            'status' => CredentialStatus::Planned,
            'issuer' => 'ISO',
            'name_ar' => $ar,
            'name_en' => $en,
            'credential_number' => null,
            'summary_ar' => $summary,
            'notes' => 'خارطة طريق: لا تُعرض للجمهور إلا بعد الحصول على الشهادة الفعلية وتغيير الحالة إلى Verified. '.$code,
        ];

        $saudi = fn (string $slug, CredentialType $type, string $ar, string $en, string $summary, string $classification) => [
            'slug' => $slug,
            'credential_type' => $type,
            'status' => CredentialStatus::Pending,
            'issuer' => 'الجهة الرسمية المختصة',
            'name_ar' => $ar,
            'name_en' => $en,
            'credential_number' => null,
            'summary_ar' => $summary,
            'notes' => "تصنيف: {$classification}. يُدخَل رقم الوثيقة الفعلي وتُغيَّر الحالة إلى Verified عند توفره.",
        ];

        return [
            $iso('iso-9001', 'ISO 9001', 'ISO 9001 — إدارة الجودة', 'ISO 9001 Quality Management', 'اعتماد خارجي مخطّط لإدارة الجودة.'),
            $iso('iso-14001', 'ISO 14001', 'ISO 14001 — الإدارة البيئية', 'ISO 14001 Environmental Management', 'اعتماد خارجي مخطّط للإدارة البيئية.'),
            $iso('iso-45001', 'ISO 45001', 'ISO 45001 — السلامة والصحة المهنية', 'ISO 45001 Occupational Health & Safety', 'اعتماد خارجي مخطّط للسلامة والصحة المهنية.'),
            $iso('iso-27001', 'ISO/IEC 27001', 'ISO/IEC 27001 — أمن المعلومات', 'ISO/IEC 27001 Information Security', 'اعتماد خارجي مخطّط لأمن المعلومات.'),
            $saudi('commercial-registration', CredentialType::Registration, 'السجل التجاري', 'Commercial Registration', 'تسجيل النشاط التجاري لدى وزارة التجارة.', 'REQUIRED'),
            $saudi('municipal-license', CredentialType::License, 'الرخصة البلدية', 'Municipal License', 'رخصة مزاولة النشاط من البلدية / منصة بلدي.', 'REQUIRED'),
            $saudi('vat-registration', CredentialType::Registration, 'التسجيل في ضريبة القيمة المضافة', 'VAT Registration', 'التسجيل الضريبي لدى هيئة الزكاة والضريبة والجمارك عند بلوغ الحد.', 'RECOMMENDED'),
            $saudi('pest-control-permit', CredentialType::Permit, 'تصريح مكافحة الآفات', 'Pest Control Permit', 'تصريح خاص بخدمات مكافحة الآفات عند تقديمها بمبيدات منظّمة.', 'OPTIONAL'),
        ];
    }

    /**
     * Assemble a standard's full document body from its parts, with a fixed
     * professional structure and the mandatory internal-standard footer.
     *
     * @param  list<string>  $responsibilities
     * @param  list<string>  $procedures
     * @param  list<string>  $exceptions
     * @param  list<string>  $related
     */
    private function body(string $purpose, string $scope, array $responsibilities, array $procedures, array $exceptions, array $related): string
    {
        return implode("\n", [
            '<h2>الغرض</h2>',
            '<p>'.$purpose.'</p>',
            '<h2>النطاق</h2>',
            '<p>'.$scope.'</p>',
            '<h2>المسؤوليات</h2>',
            '<ul>'.implode('', $responsibilities).'</ul>',
            '<h2>الإجراءات</h2>',
            '<ol>'.implode('', $procedures).'</ol>',
            '<h2>ضوابط وحدود</h2>',
            '<ul>'.implode('', $exceptions).'</ul>',
            '<h2>المراجعة والتحديث</h2>',
            '<p>يُراجَع هذا المعيار دوريًا ويُحدَّث عند تغيّر طريقة العمل. أي تعديل يرفع رقم الإصدار.</p>',
            '<h2>سياسات ومعايير ذات صلة</h2>',
            '<ul>'.implode('', array_map(fn ($r) => '<li>'.$r.'</li>', $related)).'</ul>',
            '<h2>الاعتماد</h2>',
            '<p><strong>معيار تشغيلي داخلي صادر عن فايب كلين برو</strong> — ليس شهادة ISO ولا اعتمادًا حكوميًا. مُعتمد داخليًا ضمن نظام تشغيل الشركة.</p>',
        ]);
    }
}
