<?php

namespace App\Enums;

enum MediaStatus: string
{
    case Ready = 'ready';
    case Pending = 'pending';
    case Private = 'private';
    case Replace = 'replace';

    public function label(): string
    {
        return match ($this) {
            self::Ready => 'جاهز للنشر',
            self::Pending => 'بانتظار المراجعة',
            self::Private => 'محجوب (خصوصية)',
            self::Replace => 'يُستبدل لاحقًا',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ready => 'success',
            self::Pending => 'warning',
            self::Private => 'danger',
            self::Replace => 'gray',
        };
    }

    /**
     * Statuses a public, published page may show.
     */
    public function isPublishable(): bool
    {
        return $this === self::Ready || $this === self::Replace;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
