<?php

namespace App\Enums;

/**
 * What kind of work a case study documents.
 *
 * A cluster is coarser than a service and sharper than "project": a villa
 * job and a facade job can share the villa-cleaning service and still be
 * different stories. Its job here is internal linking - a case study
 * should sit beside its own kind, so a reader following the evidence
 * stays inside one topic instead of bouncing between a tower facade and a
 * sofa.
 *
 * Government exists in the list because the owner named it. No project
 * carries it: nothing in the library evidences a government client, and a
 * cluster assigned without evidence is a claim about a customer we cannot
 * make. It stays available in the panel for the day that changes.
 */
enum ProjectCluster: string
{
    case Residential = 'residential';
    case Villa = 'villa';
    case Commercial = 'commercial';
    case Facade = 'facade';
    case PostConstruction = 'post_construction';
    case Industrial = 'industrial';
    case Government = 'government';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'سكني',
            self::Villa => 'فلل وقصور',
            self::Commercial => 'تجاري ومكاتب',
            self::Facade => 'واجهات وارتفاعات',
            self::PostConstruction => 'ما بعد البناء',
            self::Industrial => 'صناعي ومستودعات',
            self::Government => 'جهات حكومية',
        };
    }

    /**
     * What this cluster covers, for whoever has to act on it.
     *
     * It is an internal note, not page copy: it tells an editor which
     * projects belong here, what a future cluster landing page would be
     * about, and which service family it feeds. Nothing renders it to a
     * visitor today - no cluster pages exist yet, and inventing them
     * would create thin archive pages nobody searched for.
     */
    public function description(): string
    {
        return match ($this) {
            self::Residential => 'أعمال داخل مساكن قائمة ومأهولة: شقق ومنازل وما يتبعها من مفروشات وخزانات ومكيفات. نية البحث شرائية متكررة وقيمة العملية أقل، لكنها الأكثر تكرارًا — وتغذي صفحات تنظيف المنازل والشقق والكنب والسجاد.',
            self::Villa => 'فلل وقصور يمتد فيها العمل عادة من الداخل إلى الحوش والواجهة في زيارة واحدة. قيمة العملية أعلى من السكني العادي وتقبل خدمات متعددة في العقد نفسه — تغذي صفحة تنظيف الفلل وتدعم الأحواش وجلي الرخام.',
            self::Commercial => 'مكاتب ومقرات ومحلات ومعارض ومرافق مؤسسية. النية B2B والقرار إداري، والقيمة الحقيقية في تحوّلها إلى عقد دوري لا زيارة — تغذي تنظيف المكاتب والمحلات وعقود النظافة.',
            self::Facade => 'أعمال الواجهات والزجاج على ارتفاع: حبال ورافعات وسقالات. أعلى عناقيد المكتبة قيمة تجارية وأصعبها تقليدًا، لأن الدليل المصوَّر عليها يصعب اختلاقه — تغذي تنظيف الواجهات والزجاج.',
            self::PostConstruction => 'تنظيف تسليمي بعد البناء أو التشطيب، قبل السكن أو الاستلام. نية شرائية عالية ومحددة زمنيًا: العميل يبحث مرة واحدة ويقرر بسرعة — تغذي صفحة ما بعد التشطيب وتدعم الفلل وجلي الرخام.',
            self::Industrial => 'مستودعات ومنشآت صناعية خفيفة: أرضيات خرسانية وإيبوكسي وخطوط ممرات أمان. عنقود صغير اليوم لكن قيمته التشغيلية عالية، ويناسب عقود الصيانة الدورية.',
            self::Government => 'جهات ومرافق حكومية. لا مشروع في هذا العنقود اليوم — لا يوجد في المكتبة ما يثبت عميلًا حكوميًا، ولا يُصنَّف مشروع هنا إلا بدليل وإذن نشر صريح.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }

    /**
     * The service a case study leads with decides its cluster. This is a
     * derivation from data already on the project, never a guess: a
     * project with no primary service gets no cluster.
     */
    public static function fromServiceSlug(?string $slug): ?self
    {
        return match ($slug) {
            'facade-cleaning', 'glass-cleaning' => self::Facade,
            'post-construction-cleaning' => self::PostConstruction,
            'villa-cleaning' => self::Villa,
            'office-cleaning', 'shop-cleaning', 'cleaning-contracts' => self::Commercial,
            'home-cleaning', 'apartment-cleaning', 'sofa-cleaning', 'carpet-cleaning',
            'majlis-cleaning', 'ac-cleaning', 'water-tank-cleaning', 'disinfection',
            'pest-control', 'pool-cleaning', 'courtyard-cleaning', 'marble-polishing' => self::Residential,
            default => null,
        };
    }
}
