<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Quoted = 'quoted';
    case Won = 'won';
    case Completed = 'completed';
    case Lost = 'lost';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Contacted => 'تم التواصل',
            self::Qualified => 'مؤهل',
            self::Quoted => 'تم إرسال عرض سعر',
            self::Won => 'قَبِل العرض',
            self::Completed => 'تم التنفيذ',
            self::Lost => 'مرفوض / خسارة',
            self::Spam => 'غير مرغوب (Spam)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Contacted, self::Qualified, self::Quoted => 'warning',
            self::Won, self::Completed => 'success',
            self::Lost, self::Spam => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
