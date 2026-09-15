<?php

namespace App\Filament\Resources\TeamMembers;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Filament\Support\MediaPicker;
use App\Models\TeamMember;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The people shown on the About page. Hiding is a toggle (is_active),
 * never a delete, and display order is drag-and-drop on the list.
 * Every field here is one the public page actually renders - there is
 * no room for years-of-experience or awards unless a real column for a
 * real fact is added later.
 */
class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'عضو فريق';

    protected static ?string $pluralModelLabel = 'الفريق';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('الاسم')->required()->maxLength(150),
            TextInput::make('role_title')->label('المسمى الوظيفي')->maxLength(150),
            MediaPicker::make('photo_media_id', 'الصورة الشخصية'),
            Toggle::make('is_active')
                ->label('ظاهر في صفحة من نحن')
                ->helperText('أطفئه لإخفاء العضو دون حذف بياناته.')
                ->default(true),
            Textarea::make('bio')->label('نبذة قصيرة')->rows(3)->maxLength(600)->columnSpanFull(),
            TextInput::make('sort_order')
                ->label('ترتيب العرض')
                ->numeric()
                ->default(0)
                ->helperText('يمكنك أيضًا سحب الأعضاء وإفلاتهم لترتيبهم من القائمة.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('role_title')->label('المسمى الوظيفي'),
                IconColumn::make('photo_media_id')->label('صورة')->boolean(),
                IconColumn::make('is_active')->label('ظاهر')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                TernaryFilter::make('is_active')->label('ظاهر'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamMembers::route('/'),
            'create' => CreateTeamMember::route('/create'),
            'edit' => EditTeamMember::route('/{record}/edit'),
        ];
    }
}
