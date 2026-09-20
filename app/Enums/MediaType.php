<?php

namespace App\Enums;

/**
 * What a media file is allowed to say about the company.
 *
 * Three tiers decide every use on the site, and the rules below are the
 * single source for them - the Publishing Gate, the project picker and
 * the panel all read from here rather than repeating the logic:
 *
 *  - Real:         the company's own photograph. Hero, gallery, projects,
 *                  before/after - the only tier that proves work happened.
 *  - Illustration: a cover drawn in the brand's identity. It may carry a
 *                  service page that has no photo yet, and nothing else:
 *                  never a gallery, never a project, never evidence.
 *  - Placeholder:  a temporary stand-in. Never publishable at all.
 *
 * Stock predates these tiers and stays for licensed photography the site
 * already uses (the homepage hero). It is not evidence, and like an
 * Illustration it may only sit where a page needs a picture, never where
 * a visitor would read it as our work.
 */
enum MediaType: string
{
    case Real = 'real';
    case Stock = 'stock';
    case Illustration = 'illustration';
    case Placeholder = 'placeholder';

    public function label(): string
    {
        return match ($this) {
            self::Real => 'صورة حقيقية من أعمالنا',
            self::Stock => 'صورة مرخّصة (ليست من أعمالنا)',
            self::Illustration => 'غلاف مصمَّم بهوية العلامة (ليس صورة عمل)',
            self::Placeholder => 'صورة مؤقتة (Placeholder)',
        };
    }

    /**
     * What the panel tells an editor this file may be used for.
     */
    public function usageNote(): string
    {
        return match ($this) {
            self::Real => 'تصلح للصورة الرئيسية والمعارض وصفحات المشاريع وصور قبل/بعد.',
            self::Stock => 'تصلح كصورة عامة للصفحة فقط، ولا تُستخدم كدليل على عمل نفّذناه.',
            self::Illustration => 'تصلح كغلاف لصفحة خدمة فقط — لا تُستخدم داخل المعارض ولا في صفحات المشاريع.',
            self::Placeholder => 'لا تُنشر إطلاقًا. استبدلها بصورة معتمدة قبل نشر أي صفحة تستخدمها.',
        };
    }

    /**
     * Only the company's own photos may stand as proof of work: project
     * galleries, before/after pairs, Google Business Profile.
     */
    public function isEvidence(): bool
    {
        return $this === self::Real;
    }

    /**
     * May a visitor ever see this file? A Placeholder may not.
     */
    public function isPublishable(): bool
    {
        return $this !== self::Placeholder;
    }

    /**
     * May this file carry a service page as its cover?
     */
    public function canCoverAService(): bool
    {
        return $this->isPublishable();
    }

    /**
     * May this file sit inside a page's body - an image block, a gallery,
     * a project's before/after? A drawing may not: it would read as work
     * we did.
     */
    public function canAppearInBody(): bool
    {
        return match ($this) {
            self::Real, self::Stock => true,
            self::Illustration, self::Placeholder => false,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
