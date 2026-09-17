<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaPicker;
use App\Models\BusinessProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageBusinessProfile extends Page
{
    protected string $view = 'filament.pages.manage-business-profile';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'بيانات المنشأة';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_business_profile') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Tabs::make('business_profile')->tabs([
                        Tab::make('البيانات الأساسية')->schema([
                            Section::make('بيانات التواصل')
                                ->schema([
                                    TextInput::make('name')->label('اسم المنشأة')->required()->maxLength(255),
                                    TextInput::make('phone')->label('الهاتف')->tel(),
                                    TextInput::make('whatsapp_number')->label('رقم واتساب')->tel(),
                                    TextInput::make('email')->label('البريد الإلكتروني')->email(),
                                    Textarea::make('address')->label('العنوان')->rows(2)->columnSpanFull(),
                                    TextInput::make('city')->label('المدينة'),
                                    MediaPicker::make('logo_media_id', 'الشعار'),
                                ])
                                ->columns(2),
                            Section::make('روابط التواصل الاجتماعي')
                                ->schema([
                                    KeyValue::make('social_links')
                                        ->label('')
                                        ->keyLabel('المنصة')
                                        ->valueLabel('الرابط')
                                        ->reorderable(),
                                ]),
                            Section::make('ساعات العمل')
                                ->schema([
                                    KeyValue::make('working_hours')
                                        ->label('')
                                        ->keyLabel('اليوم')
                                        ->valueLabel('الأوقات')
                                        ->reorderable(),
                                ]),
                        ]),

                        // Everything the public About page says about the
                        // company comes from this tab - nothing there is
                        // written in a template.
                        Tab::make('هوية الشركة')->schema([
                            Section::make('التعريف')
                                ->description('يظهر في مقدمة صفحة "من نحن". اكتب حقائق فقط.')
                                ->schema([
                                    TextInput::make('tagline')
                                        ->label('الوصف المختصر')
                                        ->maxLength(180)
                                        ->helperText('جملة واحدة تعرّف الشركة، مثل: شركة تنظيف سعودية تخدم الرياض.')
                                        ->columnSpanFull(),
                                    Textarea::make('identity_statement')
                                        ->label('بيان الهوية المحلية')
                                        ->rows(3)
                                        ->helperText('من نحن ومن يملك الشركة وأين نعمل - كما هو فعليًا.')
                                        ->columnSpanFull(),
                                ]),
                            Section::make('القصة والمبادئ')
                                ->schema([
                                    RichEditor::make('story')
                                        ->label('قصة التأسيس')
                                        ->helperText('لماذا تأسست الشركة وكيف تعمل. اتركه فارغًا ولن يظهر القسم.')
                                        ->columnSpanFull(),
                                    Textarea::make('mission')->label('الرسالة')->rows(3),
                                    Textarea::make('vision')->label('الرؤية')->rows(3)->helperText('اختياري.'),
                                    Repeater::make('values')
                                        ->label('القيم ومبادئ العمل')
                                        ->schema([
                                            TextInput::make('title')->label('المبدأ')->required()->maxLength(80),
                                            Textarea::make('description')->label('ماذا يعني عمليًا')->rows(2)->maxLength(300),
                                        ])
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                        ->addActionLabel('إضافة مبدأ')
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                        Tab::make('المؤسس')->schema([
                            Section::make()
                                ->schema([
                                    Toggle::make('show_founder')
                                        ->label('إظهار قسم المؤسس في صفحة من نحن')
                                        ->default(true)
                                        ->columnSpanFull(),
                                    TextInput::make('founder_name')->label('اسم المؤسس')->maxLength(150),
                                    TextInput::make('founder_title')->label('الصفة المهنية')->maxLength(150)->helperText('مثل: المؤسس والمدير التنفيذي.'),
                                    MediaPicker::make('founder_photo_media_id', 'صورة المؤسس'),
                                    Textarea::make('founder_bio')->label('نبذة قصيرة')->rows(3)->maxLength(600)->columnSpanFull(),
                                    RichEditor::make('founder_long_bio')->label('نبذة موسعة')->helperText('اختياري.')->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                        Tab::make('الإشعارات')->schema([
                            Section::make('إشعارات الطلبات الجديدة')
                                ->description('عند وصول طلب عرض سعر أو رسالة تواصل جديدة يُرسل بريد داخلي إلى هذا العنوان. يُحفظ الطلب في لوحة الإدارة في كل الأحوال، حتى لو كان الحقل فارغًا أو تعذّر إرسال البريد.')
                                ->schema([
                                    TextInput::make('lead_notification_email')
                                        ->label('بريد استقبال الطلبات')
                                        ->email()
                                        ->maxLength(255)
                                        ->helperText('عنوان داخلي يتابعه الفريق، مستقل عن البريد المعروض للعملاء في الموقع. اتركه فارغًا لإيقاف الإشعارات.'),
                                ]),
                        ]),

                        Tab::make('الفريق')->schema([
                            Section::make()
                                ->description('أعضاء الفريق أنفسهم يُدارون من قائمة "الفريق" في القائمة الجانبية: إضافة، تعديل، إخفاء، وترتيب بالسحب.')
                                ->schema([
                                    Toggle::make('show_team')
                                        ->label('إظهار قسم الفريق في صفحة من نحن')
                                        ->default(true),
                                ]),
                        ]),
                    ])->persistTabInQueryString(),
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
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->getRecord()->fill($data)->save();

        Notification::make()->success()->title('تم الحفظ')->send();
    }

    public function getRecord(): BusinessProfile
    {
        return BusinessProfile::query()->firstOrNew(['id' => 1]);
    }
}
