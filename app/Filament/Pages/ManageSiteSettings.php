<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaPicker;
use App\Models\SiteSetting;
use App\Support\Analytics\AnalyticsSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Deliberately minimal: only settings with a real, current use are exposed
 * here (analytics + one SEO default). No speculative keys for features
 * that don't exist yet - business identity/contact data belongs on
 * ManageBusinessProfile instead, not duplicated here.
 *
 * @property-read Schema $form
 */
class ManageSiteSettings extends Page
{
    protected string $view = 'filament.pages.manage-site-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'إعدادات الموقع';

    /**
     * Setting key => stored type.
     *
     * @var array<string, string>
     */
    private const KEYS = [
        AnalyticsSettings::GA4_ID_KEY => 'string',
        AnalyticsSettings::GA4_ENABLED_KEY => 'bool',
        AnalyticsSettings::SEARCH_CONSOLE_KEY => 'string',
        'google_tag_manager_id' => 'string',
        'default_meta_title_suffix' => 'string',
        SiteSetting::HOME_HERO_MEDIA_ID => 'int',
    ];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_site_settings') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(
            collect(self::KEYS)->mapWithKeys(fn (string $type, string $key) => [$key => SiteSetting::get($key)])->all()
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('الصفحة الرئيسية')
                        ->schema([
                            MediaPicker::make(SiteSetting::HOME_HERO_MEDIA_ID, 'صورة الواجهة (Hero)')
                                ->helperText('صورة من مكتبة الوسائط تظهر خلف عنوان الصفحة الرئيسية. اتركها فارغة ليُستخدم أحدث صورة "بعد" من الأعمال المنشورة.'),
                        ]),
                    Section::make('Google Search Console')
                        ->schema([
                            TextInput::make(AnalyticsSettings::SEARCH_CONSOLE_KEY)
                                ->label('رمز التحقق (google-site-verification)')
                                ->regex(AnalyticsSettings::TOKEN_PATTERN)
                                ->helperText('قيمة content فقط من وسم التحقق، لا الوسم كاملًا. يُطبع الوسم في الموقع عند وجود قيمة صحيحة.'),
                        ]),
                    Section::make('التحليلات (Analytics)')
                        ->description('GA4 لا يبدأ بمجرد إدخال المعرّف: يلزم تفعيله صراحةً، ونشر سياسة الخصوصية أولًا.')
                        ->schema([
                            TextInput::make(AnalyticsSettings::GA4_ID_KEY)->label('GA4 Measurement ID')->placeholder('G-XXXXXXX')->regex(AnalyticsSettings::GA4_PATTERN),
                            Toggle::make(AnalyticsSettings::GA4_ENABLED_KEY)
                                ->label('تفعيل GA4')
                                ->helperText('لا يعمل ما دامت صفحة سياسة الخصوصية غير منشورة. حسم الموافقة على ملفات الارتباط قرار قانوني للمالك.'),
                            TextInput::make('google_tag_manager_id')->label('Google Tag Manager ID')->placeholder('GTM-XXXXXXX')->helperText('محفوظ فقط — غير مطبوع في الموقع.'),
                        ])
                        ->columns(2),
                    Section::make('إعدادات SEO الافتراضية')
                        ->schema([
                            TextInput::make('default_meta_title_suffix')
                                ->label('لاحقة عنوان SEO الافتراضية')
                                ->placeholder('| Vibe Clean Pro')
                                ->helperText('تُضاف تلقائيًا لعناوين الصفحات التي لا تملك عنوان SEO مخصصًا (يُطبَّق لاحقًا عند بناء صفحات الموقع).'),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('حفظ')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (self::KEYS as $key => $type) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $data[$key] ?? null, 'type' => $type],
            );
        }

        Notification::make()->success()->title('تم الحفظ')->send();
    }
}
