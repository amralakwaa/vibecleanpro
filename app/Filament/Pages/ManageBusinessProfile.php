<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaPicker;
use App\Models\BusinessProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
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
                    Section::make('البيانات الأساسية')
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
