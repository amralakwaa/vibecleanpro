<?php

namespace App\Filament\Pages;

use App\Listeners\LogSecurityEvent;
use App\Support\Analytics\AnalyticsSettings;
use App\Support\Backup\BackupSettings;
use App\Support\Backup\ExternalBackupDestinationRegistry;
use App\Support\Mail\MailSettings;
use App\Support\Readiness\LaunchReadiness;
use App\Support\Settings\IntegrationSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

/**
 * One place for the operational integrations: mail, backups, Google.
 *
 * Secrets (the SMTP password, external backup keys) are write-only here:
 * the field shows whether one is stored, never the value, and leaving it
 * empty keeps the stored one. Audit entries record that settings changed
 * and by whom - never what the values were.
 */
class ManageIntegrations extends Page
{
    protected string $view = 'filament.pages.manage-integrations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'إعدادات النظام والتكاملات';

    protected static ?string $navigationLabel = 'النظام والتكاملات';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->can('manage_site_settings') ?? false;
    }

    public function mount(): void
    {
        $settings = app(IntegrationSettings::class);
        $mail = app(MailSettings::class);
        $backups = app(BackupSettings::class);
        $values = $mail->values();

        $this->form->fill([
            IntegrationSettings::SMTP_ENABLED => $mail->isEnabled(),
            IntegrationSettings::SMTP_HOST => $values['host'],
            IntegrationSettings::SMTP_PORT => $values['port'],
            IntegrationSettings::SMTP_ENCRYPTION => $values['encryption'],
            IntegrationSettings::SMTP_USERNAME => $values['username'],
            IntegrationSettings::SMTP_PASSWORD => null,
            IntegrationSettings::SMTP_FROM_ADDRESS => $values['from_address'],
            IntegrationSettings::SMTP_FROM_NAME => $values['from_name'],
            IntegrationSettings::BACKUP_LOCAL_ENABLED => $backups->localEnabled(),
            IntegrationSettings::BACKUP_TIME => $backups->dailyTime(),
            IntegrationSettings::BACKUP_KEEP => $backups->keep(),
            IntegrationSettings::BACKUP_INCLUDE_DATABASE => $backups->includesDatabase(),
            IntegrationSettings::BACKUP_INCLUDE_MEDIA => $backups->includesMedia(),
            IntegrationSettings::BACKUP_EXTERNAL_ENABLED => $backups->externalEnabled(),
            IntegrationSettings::BACKUP_EXTERNAL_PROVIDER => $backups->externalProvider(),
            IntegrationSettings::BACKUP_EXTERNAL_DESTINATION => $backups->externalDestination(),
            IntegrationSettings::BACKUP_EXTERNAL_KEY => null,
            IntegrationSettings::BACKUP_EXTERNAL_SECRET => null,
            AnalyticsSettings::SEARCH_CONSOLE_KEY => $settings->string(AnalyticsSettings::SEARCH_CONSOLE_KEY),
            IntegrationSettings::SEARCH_CONSOLE_ENABLED => $settings->bool(IntegrationSettings::SEARCH_CONSOLE_ENABLED),
            AnalyticsSettings::GA4_ID_KEY => $settings->string(AnalyticsSettings::GA4_ID_KEY),
            AnalyticsSettings::GA4_ENABLED_KEY => $settings->bool(AnalyticsSettings::GA4_ENABLED_KEY),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $mail = app(MailSettings::class);
        $backups = app(BackupSettings::class);

        return $schema
            ->components([
                Form::make([
                    Tabs::make('integrations')->columnSpanFull()->tabs([
                        Tab::make('البريد (SMTP)')->icon('heroicon-o-envelope')->schema([
                            Section::make('إعدادات الإرسال')
                                ->description($mail->environmentControlsMail()
                                    ? 'البريد مضبوط حاليًا في بيئة الخادم (.env)، وهي الأعلى أولوية — القيم هنا لا تُطبَّق ما دام ذلك قائمًا.'
                                    : 'تُستخدم هذه القيم لإرسال إشعارات الطلبات. كلمة المرور تُحفظ مشفّرة ولا تُعرض بعد الحفظ.')
                                ->schema([
                                    Toggle::make(IntegrationSettings::SMTP_ENABLED)->label('تفعيل الإرسال عبر SMTP')->columnSpanFull(),
                                    TextInput::make(IntegrationSettings::SMTP_HOST)->label('الخادم (Host)')->maxLength(255)
                                        ->regex('/^[A-Za-z0-9.\-_:]+$/')->requiredIf(IntegrationSettings::SMTP_ENABLED, true),
                                    TextInput::make(IntegrationSettings::SMTP_PORT)->label('المنفذ (Port)')->numeric()->minValue(1)->maxValue(65535)->default(587),
                                    Select::make(IntegrationSettings::SMTP_ENCRYPTION)->label('التشفير')->options(MailSettings::ENCRYPTIONS)->default('tls'),
                                    TextInput::make(IntegrationSettings::SMTP_USERNAME)->label('اسم المستخدم')->maxLength(255),
                                    TextInput::make(IntegrationSettings::SMTP_PASSWORD)
                                        ->label('كلمة المرور')
                                        ->password()
                                        ->revealable(false)
                                        ->autocomplete('new-password')
                                        ->maxLength(255)
                                        ->helperText($mail->hasPassword()
                                            ? 'كلمة مرور محفوظة (مشفّرة). اتركه فارغًا للإبقاء عليها، أو اكتب واحدة جديدة لاستبدالها.'
                                            : 'لا توجد كلمة مرور محفوظة بعد.'),
                                    TextInput::make(IntegrationSettings::SMTP_FROM_ADDRESS)->label('بريد المرسِل')->email()->maxLength(255),
                                    TextInput::make(IntegrationSettings::SMTP_FROM_NAME)->label('اسم المرسِل')->maxLength(255),
                                ])
                                ->columns(2),
                            Section::make('بريد استقبال الطلبات')
                                ->description('يُدار من «بيانات المنشأة ← الإشعارات»، ويجب أن يكون موجودًا حتى تصل إشعارات الطلبات.')
                                ->schema([
                                    Text::make(fn (): string => $mail->notificationRecipient() ?? 'غير محدد — لن تصلك إشعارات بالطلبات الجديدة.'),
                                ]),
                        ]),

                        Tab::make('النسخ الاحتياطي')->icon('heroicon-o-server-stack')->schema([
                            Section::make('النسخ المحلي')
                                ->description('تُكتب النسخ في تخزين خاص على الخادم نفسه، مع بصمات تحقق.')
                                ->schema([
                                    Toggle::make(IntegrationSettings::BACKUP_LOCAL_ENABLED)->label('تفعيل النسخ اليومي')->columnSpanFull(),
                                    TextInput::make(IntegrationSettings::BACKUP_TIME)->label('وقت التشغيل اليومي')->regex('/^([01]\d|2[0-3]):[0-5]\d$/')->placeholder('02:30'),
                                    TextInput::make(IntegrationSettings::BACKUP_KEEP)->label('عدد النسخ المحفوظة')->numeric()->minValue(1)->maxValue(365),
                                    Toggle::make(IntegrationSettings::BACKUP_INCLUDE_DATABASE)->label('تضمين قاعدة البيانات'),
                                    Toggle::make(IntegrationSettings::BACKUP_INCLUDE_MEDIA)->label('تضمين الصور والملفات'),
                                ])
                                ->columns(2),
                            Section::make('النسخ خارج الخادم')
                                ->description('نسخة على الخادم نفسه لا تحميك من فقد الخادم. لا يوجد تنفيذ جاهز للرفع الخارجي بعد — يحتاج حزمة وموافقتك، ولذلك تبقى الحالة «غير مهيأ».')
                                ->schema([
                                    Toggle::make(IntegrationSettings::BACKUP_EXTERNAL_ENABLED)->label('تفعيل النسخ الخارجي')->columnSpanFull(),
                                    Select::make(IntegrationSettings::BACKUP_EXTERNAL_PROVIDER)->label('الوجهة')->options(app(ExternalBackupDestinationRegistry::class)->options()),
                                    TextInput::make(IntegrationSettings::BACKUP_EXTERNAL_DESTINATION)->label('المسار / الحاوية (Bucket)')->maxLength(255),
                                    TextInput::make(IntegrationSettings::BACKUP_EXTERNAL_KEY)->label('مفتاح الوصول')->password()->revealable(false)->maxLength(255)
                                        ->helperText($backups->hasExternalCredentials() ? 'محفوظ (مشفّر). اتركه فارغًا للإبقاء عليه.' : 'غير محفوظ.'),
                                    TextInput::make(IntegrationSettings::BACKUP_EXTERNAL_SECRET)->label('المفتاح السري')->password()->revealable(false)->maxLength(255),
                                ])
                                ->columns(2),
                        ]),

                        Tab::make('Google والتحليلات')->icon('heroicon-o-chart-bar')->schema([
                            Section::make('Google Search Console')
                                ->schema([
                                    TextInput::make(AnalyticsSettings::SEARCH_CONSOLE_KEY)
                                        ->label('رمز التحقق (google-site-verification)')
                                        ->regex(AnalyticsSettings::TOKEN_PATTERN)
                                        ->maxLength(100)
                                        ->helperText('قيمة content فقط، لا الوسم كاملًا.'),
                                    Toggle::make(IntegrationSettings::SEARCH_CONSOLE_ENABLED)->label('طباعة وسم التحقق في الموقع'),
                                ])
                                ->columns(2),
                            Section::make('Google Analytics 4')
                                ->description('GA4 لا يعمل بمجرد إدخال المعرّف: يلزم تفعيله، ونشر سياسة الخصوصية، وحسم آلية الموافقة.')
                                ->schema([
                                    TextInput::make(AnalyticsSettings::GA4_ID_KEY)->label('Measurement ID')->placeholder('G-XXXXXXXXXX')->regex(AnalyticsSettings::GA4_PATTERN),
                                    Toggle::make(AnalyticsSettings::GA4_ENABLED_KEY)->label('تفعيل GA4')
                                        ->helperText(fn (): string => app(AnalyticsSettings::class)->privacyPolicyPublished()
                                            ? 'سياسة الخصوصية منشورة.'
                                            : 'سياسة الخصوصية غير منشورة — سيبقى GA4 متوقفًا حتى تُنشر.'),
                                ])
                                ->columns(2),
                            Section::make('الملف التجاري على Google والسجل التجاري')
                                ->description('روابط Google والسجل التجاري تُدار من شاشة «بيانات المنشأة» حتى لا تتكرر في مكانين.')
                                ->schema([
                                    Text::make(fn (): string => collect(app(LaunchReadiness::class)->all())
                                        ->whereIn('key', ['gbp', 'google_reviews', 'commercial_registration'])
                                        ->map(fn ($item) => "{$item->title}: {$item->label} — {$item->detail}")
                                        ->implode("\n")),
                                ]),
                        ]),
                    ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')->label('حفظ')->submit('save')->keyBindings(['mod+s']),
                            $this->testMailAction(),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Sends one message through the configured transport. In local and
     * testing this is whatever mailer the environment uses (log/array), so
     * nothing leaves the machine; on a real server it is a genuine test.
     */
    public function testMailAction(): Action
    {
        return Action::make('testMail')
            ->label('إرسال رسالة اختبار')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(fn (): string => 'تُرسل رسالة اختبار إلى بريد استقبال الطلبات المحفوظ. احفظ الإعدادات أولًا.')
            ->action(fn () => $this->sendTestMessage());
    }

    /**
     * Sends the test message to the stored inbox. Any transport failure is
     * reported as a notification, never as a stack trace, and the message
     * itself carries no credential.
     */
    public function sendTestMessage(): void
    {
        $mail = app(MailSettings::class);
        $recipient = $mail->notificationRecipient();

        if (blank($recipient)) {
            Notification::make()->warning()->title('لا يوجد بريد لاستقبال الطلبات')->body('أضِفه في «بيانات المنشأة ← الإشعارات» أولًا.')->send();

            return;
        }

        try {
            $mail->applyRuntimeConfiguration();
            Mail::raw('رسالة اختبار من لوحة تحكم فايب كلين برو. وصولها يعني أن إعدادات البريد تعمل.', fn ($message) => $message->to($recipient)->subject('اختبار إعدادات البريد — فايب كلين برو'));

            LogSecurityEvent::record('settings.smtp_test', Auth::user());
            Notification::make()->success()->title('أُرسلت رسالة الاختبار')->body("الوجهة: {$recipient}. تحقق من وصولها (وصندوق الرسائل المزعجة).")->send();
        } catch (Throwable $exception) {
            Notification::make()->danger()->title('تعذّر الإرسال')->body(mb_substr($exception->getMessage(), 0, 300))->send();
        }
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(IntegrationSettings::class);

        $plain = [
            IntegrationSettings::SMTP_HOST => 'string',
            IntegrationSettings::SMTP_PORT => 'int',
            IntegrationSettings::SMTP_ENCRYPTION => 'string',
            IntegrationSettings::SMTP_USERNAME => 'string',
            IntegrationSettings::SMTP_FROM_ADDRESS => 'string',
            IntegrationSettings::SMTP_FROM_NAME => 'string',
            IntegrationSettings::SMTP_ENABLED => 'bool',
            IntegrationSettings::BACKUP_LOCAL_ENABLED => 'bool',
            IntegrationSettings::BACKUP_TIME => 'string',
            IntegrationSettings::BACKUP_KEEP => 'int',
            IntegrationSettings::BACKUP_INCLUDE_DATABASE => 'bool',
            IntegrationSettings::BACKUP_INCLUDE_MEDIA => 'bool',
            IntegrationSettings::BACKUP_EXTERNAL_ENABLED => 'bool',
            IntegrationSettings::BACKUP_EXTERNAL_PROVIDER => 'string',
            IntegrationSettings::BACKUP_EXTERNAL_DESTINATION => 'string',
            AnalyticsSettings::SEARCH_CONSOLE_KEY => 'string',
            IntegrationSettings::SEARCH_CONSOLE_ENABLED => 'bool',
            AnalyticsSettings::GA4_ID_KEY => 'string',
            AnalyticsSettings::GA4_ENABLED_KEY => 'bool',
        ];

        foreach ($plain as $key => $type) {
            $settings->set($key, $data[$key] ?? null, $type);
        }

        foreach (IntegrationSettings::SECRET_KEYS as $secretKey) {
            $settings->putSecret($secretKey, $data[$secretKey] ?? null);
        }

        // The audit records that settings changed and by whom - never the
        // values, so no secret can leak through the audit trail.
        LogSecurityEvent::record('settings.integrations_updated', Auth::user());

        $this->mount();

        Notification::make()->success()->title('تم الحفظ')->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['groups' => app(LaunchReadiness::class)->groups()];
    }
}
