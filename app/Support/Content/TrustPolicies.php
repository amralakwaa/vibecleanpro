<?php

namespace App\Support\Content;

/**
 * Presentation metadata for the Trust Center page family.
 *
 * This is UI/navigation metadata only - which icon, eyebrow label and
 * topical one-liner a policy shows on hub and related-policy cards, and
 * the order the family reads in. It states no business facts: no numbers,
 * guarantees, prices, licences or rights. The authoritative policy wording
 * lives in each page's DB content blocks and is never duplicated here.
 *
 * `accent` chooses between the two brand surfaces allowed for an icon chip
 * (Action Blue and Deep Navy) so the cards read as one coordinated family
 * rather than a rainbow - see resources/css/app.css colour roles.
 */
class TrustPolicies
{
    /**
     * The policy pages, in reading order. The hub (`trust`) is the parent
     * and is not itself listed as one of its children.
     *
     * @var array<string, array{icon: string, eyebrow: string, blurb: string, accent: string}>
     */
    private const POLICIES = [
        'warranty' => [
            'icon' => 'sparkles',
            'eyebrow' => 'الضمان',
            'blurb' => 'متى تُراجَع نتيجة العمل، وما يشمله الالتزام وما لا يشمله.',
            'accent' => 'primary',
        ],
        'service-scope' => [
            'icon' => 'clipboard',
            'eyebrow' => 'نطاق الخدمة',
            'blurb' => 'ما يُغطيه العمل عادةً، ومسؤوليات كل طرف قبل التنفيذ وأثناءه.',
            'accent' => 'ink',
        ],
        'cancellation' => [
            'icon' => 'calendar',
            'eyebrow' => 'الإلغاء والمواعيد',
            'blurb' => 'كيف تُعدّل موعدك أو تُلغيه، وأثر ذلك على عرض السعر.',
            'accent' => 'primary',
        ],
        'complaints' => [
            'icon' => 'inbox',
            'eyebrow' => 'الشكاوى',
            'blurb' => 'كيف تصل ملاحظتك إلينا، وما تحتاجه لتقديم شكوى واضحة.',
            'accent' => 'ink',
        ],
        'terms' => [
            'icon' => 'scale',
            'eyebrow' => 'الشروط',
            'blurb' => 'قواعد استخدام الموقع وطلب الخدمة والعلاقة بين الطرفين.',
            'accent' => 'primary',
        ],
        'privacy' => [
            'icon' => 'lock',
            'eyebrow' => 'الخصوصية',
            'blurb' => 'ما نجمعه من بيانات ولماذا، وكيف يُحفظ ويُستخدم.',
            'accent' => 'ink',
        ],
        'licenses-compliance' => [
            'icon' => 'badge-check',
            'eyebrow' => 'الامتثال',
            'blurb' => 'نهجنا في الالتزام النظامي، وما نُصرّح به وما لا نُصرّح به.',
            'accent' => 'primary',
        ],
    ];

    private const HUB_SLUG = 'trust';

    private const HUB_META = [
        'icon' => 'shield-check',
        'eyebrow' => 'مركز الثقة',
    ];

    /**
     * @return array<string, array{icon: string, eyebrow: string, blurb: string, accent: string}>
     */
    public static function all(): array
    {
        return self::POLICIES;
    }

    public static function isHub(string $slug): bool
    {
        return $slug === self::HUB_SLUG;
    }

    public static function hubSlug(): string
    {
        return self::HUB_SLUG;
    }

    /**
     * Presentation metadata for a single page. The hub has its own icon and
     * eyebrow; an unknown slug falls back to the hub identity so the shell
     * never renders without an icon.
     *
     * @return array{icon: string, eyebrow: string}
     */
    public static function for(string $slug): array
    {
        if ($slug === self::HUB_SLUG) {
            return self::HUB_META;
        }

        $policy = self::POLICIES[$slug] ?? null;

        return $policy === null
            ? self::HUB_META
            : ['icon' => $policy['icon'], 'eyebrow' => $policy['eyebrow']];
    }
}
