<?php

namespace App\Support\Readiness;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Support\Analytics\AnalyticsSettings;
use Illuminate\Support\Facades\Storage;

/**
 * The actual state of each pre-launch dependency, computed - never
 * assumed. Each entry: state (machine key), label (Arabic, for the panel)
 * and detail (why).
 */
class LaunchReadiness
{
    public function __construct(private readonly AnalyticsSettings $analytics) {}

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function leadNotifications(): array
    {
        $recipient = BusinessProfile::query()->value('lead_notification_email');
        $mailer = (string) config('mail.default');

        return match (true) {
            blank($recipient) => ['state' => 'not_configured', 'label' => 'غير مهيأة', 'detail' => 'بريد استقبال الطلبات فارغ في بيانات المنشأة — الطلبات تُحفظ في اللوحة لكن لا يُرسل بريد.'],
            in_array($mailer, ['log', 'array'], true) => ['state' => 'not_configured', 'label' => 'غير مهيأة', 'detail' => "البريد مضبوط على «{$mailer}» — الرسائل تُكتب في السجل ولا تُرسل فعليًا."],
            default => ['state' => 'ready', 'label' => 'جاهزة', 'detail' => 'تُرسل إلى '.$recipient.'.'],
        };
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function searchConsole(): array
    {
        return $this->analytics->searchConsoleToken()
            ? ['state' => 'configured', 'label' => 'مهيأ', 'detail' => 'وسم التحقق مطبوع في الموقع.']
            : ['state' => 'not_configured', 'label' => 'غير مهيأ', 'detail' => 'أضف رمز التحقق في إعدادات الموقع.'];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function ga4(): array
    {
        return match ($this->analytics->ga4State()) {
            'active' => ['state' => 'active', 'label' => 'يعمل', 'detail' => 'سكربت GA4 مطبوع في الموقع.'],
            'configured_disabled' => ['state' => 'configured_disabled', 'label' => 'مهيأ لكنه متوقف', 'detail' => 'المعرّف موجود، لكن التفعيل مغلق أو سياسة الخصوصية غير منشورة.'],
            default => ['state' => 'not_configured', 'label' => 'غير مهيأ', 'detail' => 'لا يوجد GA4 Measurement ID صالح.'],
        };
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function privacyPolicy(): array
    {
        return $this->analytics->privacyPolicyPublished()
            ? ['state' => 'published', 'label' => 'منشورة', 'detail' => 'الرابط يظهر في نموذجي التواصل وعرض السعر.']
            : ['state' => 'draft', 'label' => 'غير منشورة', 'detail' => 'النماذج تعمل بدون رابط سياسة الخصوصية.'];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function googleReviewCta(): array
    {
        return filled(BusinessProfile::query()->value('google_review_url'))
            ? ['state' => 'visible', 'label' => 'ظاهر', 'detail' => 'يظهر في صفحة التواصل.']
            : ['state' => 'hidden', 'label' => 'مخفي', 'detail' => 'أضف رابط التقييم في بيانات المنشأة.'];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function twoFactor(): array
    {
        $active = User::query()->where('is_active', true)->count();
        $enrolled = User::query()->where('is_active', true)->whereNotNull('app_authentication_secret')->count();

        return [
            'state' => $active > 0 && $enrolled === $active ? 'complete' : 'incomplete',
            'label' => "{$enrolled} / {$active}",
            'detail' => 'مستخدمون فعّلوا التحقق الثنائي من صفحة «أمان الحساب».',
        ];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function backups(): array
    {
        $latest = collect(Storage::disk(config('backup.disk'))->directories(trim(config('backup.directory'), '/')))->sort()->last();

        if (! $latest) {
            return ['state' => 'none', 'label' => 'لا توجد', 'detail' => 'لم يُنشأ أي نسخة احتياطية بعد (backup:run).'];
        }

        $takenAt = \DateTimeImmutable::createFromFormat('Y-m-d_His', basename($latest)) ?: null;
        $isRecent = $takenAt && $takenAt > now()->subHours(26);

        return [
            'state' => $isRecent ? 'recent' : 'stale',
            'label' => $isRecent ? 'حديثة' : 'قديمة',
            'detail' => 'آخر نسخة: '.basename($latest).' (محلية على الخادم نفسه).',
        ];
    }
}
