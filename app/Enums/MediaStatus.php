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
        return in_array($this, self::publishableCases(), true);
    }

    /**
     * The single source of truth for "may appear on a public page" - used by
     * both the Media `publishable` query scope and the public media relations
     * so a render can never serve a pending or privacy-held file.
     *
     * @return array<int, self>
     */
    public static function publishableCases(): array
    {
        return [self::Ready, self::Replace];
    }

    /**
     * @return array<int, string>
     */
    public static function publishableValues(): array
    {
        return array_map(fn (self $status): string => $status->value, self::publishableCases());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
