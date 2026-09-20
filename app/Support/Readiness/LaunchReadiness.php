<?php

namespace App\Support\Readiness;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Filament\Pages\Auth\AccountSecurity;
use App\Filament\Pages\ManageBusinessProfile;
use App\Filament\Pages\ManageIntegrations;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\BusinessProfile;
use App\Models\Page;
use App\Models\User;
use App\Support\Analytics\AnalyticsSettings;
use App\Support\Backup\BackupSettings;
use App\Support\Mail\MailSettings;
use App\Support\Settings\DemoValue;

/**
 * The actual state of every operational dependency, computed - never
 * assumed. A stored value alone is never "ready": each check asks whether
 * the thing would really work right now.
 *
 * Nothing here reads a secret; at most it reports that one is stored.
 */
class LaunchReadiness
{
    public function __construct(
        private readonly AnalyticsSettings $analytics,
        private readonly MailSettings $mail,
        private readonly BackupSettings $backups,
    ) {}

    /**
     * @return array<string, list<ReadinessItem>>
     */
    public function groups(): array
    {
        return [
            'البريد والطلبات' => [$this->smtp(), $this->leadNotifications()],
            'الحسابات والأمان' => [$this->adminAccounts(), $this->twoFactor()],
            'النسخ الاحتياطي' => [$this->localBackups(), $this->externalBackups()],
            'Google' => [$this->searchConsole(), $this->ga4(), $this->googleBusinessProfile(), $this->googleReviews()],
            'المحتوى والبيانات القانونية' => [$this->commercialRegistration(), ...$this->legalPages()],
        ];
    }

    /**
     * @return list<ReadinessItem>
     */
    public function all(): array
    {
        return array_merge(...array_values($this->groups()));
    }

    public function smtp(): ReadinessItem
    {
        $state = $this->mail->readiness();

        return new ReadinessItem('smtp', 'إعدادات البريد (SMTP)', $state['state'], $state['label'], $state['detail'], $this->integrationsUrl(), 'إعداد البريد');
    }

    public function leadNotifications(): ReadinessItem
    {
        $recipient = $this->mail->notificationRecipient();

        if (blank($recipient)) {
            return new ReadinessItem('lead_notifications', 'إشعارات الطلبات', 'not_configured', 'غير مهيأة', 'بريد استقبال الطلبات فارغ — الطلبات تُحفظ في اللوحة ولا يصلك بريد.', $this->businessProfileUrl(), 'بيانات المنشأة');
        }

        return $this->mail->isReady()
            ? new ReadinessItem('lead_notifications', 'إشعارات الطلبات', 'ready', 'جاهزة', "تُرسل إلى {$recipient}.", $this->businessProfileUrl())
            : new ReadinessItem('lead_notifications', 'إشعارات الطلبات', 'error', 'لا تُرسل', "العنوان محفوظ ({$recipient}) لكن إعداد البريد غير جاهز.", $this->integrationsUrl(), 'إعداد البريد');
    }

    public function adminAccounts(): ReadinessItem
    {
        $active = User::query()->where('is_active', true)->count();
        $demo = User::query()->where('is_active', true)->get()->filter(fn (User $user) => DemoValue::isDemo($user->email))->count();
        $real = $active - $demo;

        return match (true) {
            $real === 0 => new ReadinessItem('admin_accounts', 'حسابات الإدارة', 'error', 'مطلوب إجراء', 'لا يوجد حساب إداري حقيقي (غير تجريبي) فعّال.', $this->usersUrl(), 'إدارة المستخدمين'),
            $real === 1 => new ReadinessItem('admin_accounts', 'حسابات الإدارة', 'configured', "{$real} حساب", 'حساب إداري واحد فقط — يُفضّل حساب احتياطي ثانٍ لتفادي فقد الوصول.', $this->usersUrl(), 'إدارة المستخدمين'),
            default => new ReadinessItem('admin_accounts', 'حسابات الإدارة', 'ready', "{$real} حسابات", 'حسابات إدارية فعّالة.'.($demo > 0 ? " ({$demo} حساب تجريبي محلي)" : ''), $this->usersUrl()),
        };
    }

    public function twoFactor(): ReadinessItem
    {
        $users = User::query()->where('is_active', true)->get()->reject(fn (User $user) => DemoValue::isDemo($user->email));
        $enrolled = $users->filter(fn (User $user) => filled($user->app_authentication_secret));
        $label = $enrolled->count().' / '.$users->count();

        if ($users->isEmpty()) {
            return new ReadinessItem('two_factor', 'التحقق الثنائي', 'error', $label, 'لا توجد حسابات حقيقية لتفعيله عليها.', $this->usersUrl());
        }

        $superAdminsWithout = $users->filter(fn (User $user) => $user->hasRole('Super Admin') && blank($user->app_authentication_secret));

        return match (true) {
            $superAdminsWithout->isNotEmpty() => new ReadinessItem('two_factor', 'التحقق الثنائي', 'error', $label, 'حساب Super Admin بدون تحقق ثنائي — فعّله من «أمان الحساب» قبل الإطلاق.', $this->accountSecurityUrl(), 'أمان الحساب'),
            $enrolled->count() === $users->count() => new ReadinessItem('two_factor', 'التحقق الثنائي', 'complete', $label, 'كل الحسابات الحقيقية مفعّلة.', $this->accountSecurityUrl()),
            default => new ReadinessItem('two_factor', 'التحقق الثنائي', 'configured', $label, 'بعض الحسابات لم تفعّله بعد.', $this->accountSecurityUrl(), 'أمان الحساب'),
        };
    }

    public function localBackups(): ReadinessItem
    {
        $state = $this->backups->localReadiness();

        return new ReadinessItem('backups_local', 'النسخ الاحتياطي المحلي', $state['state'], $state['label'], $state['detail'], $this->integrationsUrl(), 'إعدادات النسخ');
    }

    public function externalBackups(): ReadinessItem
    {
        $state = $this->backups->externalReadiness();

        return new ReadinessItem('backups_external', 'النسخ خارج الخادم', $state['state'], $state['label'], $state['detail'], $this->integrationsUrl(), 'إعدادات النسخ');
    }

    public function searchConsole(): ReadinessItem
    {
        if ($this->analytics->searchConsoleToken()) {
            return new ReadinessItem('search_console', 'Google Search Console', 'active', 'يعمل', 'وسم التحقق مطبوع في الموقع.', $this->integrationsUrl());
        }

        return $this->analytics->hasSearchConsoleToken()
            ? new ReadinessItem('search_console', 'Google Search Console', 'configured_disabled', 'مهيأ لكنه متوقف', 'الرمز محفوظ لكن الطباعة غير مفعّلة.', $this->integrationsUrl(), 'تفعيل')
            : new ReadinessItem('search_console', 'Google Search Console', 'not_configured', 'غير مهيأ', 'لا يوجد رمز تحقق صالح.', $this->integrationsUrl(), 'إضافة الرمز');
    }

    public function ga4(): ReadinessItem
    {
        return match ($this->analytics->ga4State()) {
            'active' => new ReadinessItem('ga4', 'Google Analytics 4', 'active', 'يعمل', 'سكربت GA4 مطبوع في الموقع.', $this->integrationsUrl()),
            'configured_disabled' => new ReadinessItem('ga4', 'Google Analytics 4', 'configured_disabled', 'مهيأ لكنه متوقف', 'المعرّف محفوظ. لا يعمل ما دام التفعيل مغلقًا أو سياسة الخصوصية غير منشورة.', $this->integrationsUrl(), 'إعدادات GA4'),
            default => new ReadinessItem('ga4', 'Google Analytics 4', 'not_configured', 'غير مهيأ', 'لا يوجد Measurement ID صالح.', $this->integrationsUrl(), 'إضافة المعرّف'),
        };
    }

    public function googleBusinessProfile(): ReadinessItem
    {
        $profile = BusinessProfile::query()->first();
        $url = $profile?->publicGoogleBusinessProfileUrl();

        return $url
            ? new ReadinessItem('gbp', 'الملف التجاري على Google', 'configured', 'مهيأ', 'الرابط معروض في صفحة التواصل.', $this->businessProfileUrl())
            : new ReadinessItem('gbp', 'الملف التجاري على Google', 'not_configured', 'غير مهيأ', filled($profile?->google_business_profile_url) ? 'القيمة المحفوظة تجريبية ولا تُعرض للزوار.' : 'لم يُضف رابط الملف التجاري.', $this->businessProfileUrl(), 'بيانات المنشأة');
    }

    public function googleReviews(): ReadinessItem
    {
        $profile = BusinessProfile::query()->first();
        $url = $profile?->publicGoogleReviewUrl();

        return $url
            ? new ReadinessItem('google_reviews', 'تقييمات Google', 'configured', 'مهيأ', 'زر التقييم يظهر في صفحة التواصل.', $this->businessProfileUrl())
            : new ReadinessItem('google_reviews', 'تقييمات Google', 'not_configured', 'غير مهيأ', filled($profile?->google_review_url) ? 'القيمة المحفوظة تجريبية ولا تُعرض للزوار.' : 'لم يُضف رابط التقييم.', $this->businessProfileUrl(), 'بيانات المنشأة');
    }

    public function commercialRegistration(): ReadinessItem
    {
        $profile = BusinessProfile::query()->first();

        if ($profile?->publicCommercialRegistration()) {
            return new ReadinessItem('commercial_registration', 'السجل التجاري', 'configured', 'معروض', 'الرقم يظهر في تذييل الموقع.', $this->businessProfileUrl());
        }

        $stored = DemoValue::realOrNull($profile?->commercial_registration_number);

        return new ReadinessItem(
            'commercial_registration',
            'السجل التجاري',
            'not_configured',
            $stored ? 'محفوظ وغير معروض' : 'غير مهيأ',
            $stored ? 'الرقم محفوظ لكن خيار العرض مغلق.' : 'لم يُدخل رقم سجل تجاري حقيقي.',
            $this->businessProfileUrl(),
            'بيانات المنشأة',
        );
    }

    /**
     * @return list<ReadinessItem>
     */
    public function legalPages(): array
    {
        $pages = [
            ['privacy', PageType::Legal, 'سياسة الخصوصية'],
            ['terms', PageType::Legal, 'الشروط والأحكام'],
            ['about', PageType::About, 'صفحة من نحن'],
        ];

        return collect($pages)->map(function (array $page) {
            [$slug, $type, $title] = $page;
            $record = Page::query()->where('slug', $slug)->where('type', $type)->first();

            return match (true) {
                ! $record => new ReadinessItem($slug, $title, 'not_configured', 'غير موجودة', 'لم تُنشأ الصفحة بعد.', null),
                $record->status === PageStatus::Published => new ReadinessItem($slug, $title, 'published', 'منشورة', 'ظاهرة للزوار.', $this->pageUrl($record)),
                default => new ReadinessItem($slug, $title, 'draft', 'مسودة', 'بانتظار قرارات المالك أو المراجعة القانونية — بوابة النشر تمنع نشرها.', $this->pageUrl($record), 'فتح الصفحة'),
            };
        })->all();
    }

    private function integrationsUrl(): ?string
    {
        return rescue(fn () => ManageIntegrations::getUrl(), null, false);
    }

    private function businessProfileUrl(): ?string
    {
        return rescue(fn () => ManageBusinessProfile::getUrl(), null, false);
    }

    private function usersUrl(): ?string
    {
        return rescue(fn () => UserResource::getUrl('index'), null, false);
    }

    private function accountSecurityUrl(): ?string
    {
        return rescue(fn () => AccountSecurity::getUrl(), null, false);
    }

    private function pageUrl(Page $page): ?string
    {
        return rescue(fn () => PageResource::getUrl('edit', ['record' => $page]), null, false);
    }
}
