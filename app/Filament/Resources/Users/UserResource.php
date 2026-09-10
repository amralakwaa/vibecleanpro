<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\UserGuardRails;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمون';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('الاسم')->required()->maxLength(255),
            TextInput::make('email')->label('البريد الإلكتروني')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
            TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                ->helperText('اتركها فارغة عند التعديل للإبقاء على كلمة المرور الحالية.'),
            Select::make('roles')
                ->label('الأدوار')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->required()
                ->disabled(fn (?User $record) => $record && UserGuardRails::isLastSuperAdmin($record))
                ->helperText(fn (?User $record) => $record && UserGuardRails::isLastSuperAdmin($record)
                    ? 'هذا آخر حساب Super Admin في النظام، لا يمكن تغيير أدواره من هنا لتجنب قفل النظام.'
                    : null),
            Toggle::make('is_active')
                ->label('نشط (يسمح بالدخول للوحة التحكم)')
                ->default(true)
                ->disabled(fn (?User $record) => $record && (
                    UserGuardRails::isSelf(Auth::user(), $record) || UserGuardRails::isLastSuperAdmin($record)
                ))
                ->helperText(fn (?User $record) => $record && UserGuardRails::isSelf(Auth::user(), $record)
                    ? 'لا يمكنك تعطيل حسابك الخاص.'
                    : ($record && UserGuardRails::isLastSuperAdmin($record) ? 'لا يمكن تعطيل آخر حساب Super Admin.' : null)),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->weight('medium'),
                TextColumn::make('email')->label('البريد الإلكتروني')->searchable(),
                TextColumn::make('roles.name')->label('الأدوار')->badge(),
                IconColumn::make('is_active')->label('نشط')->boolean(),
                TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime('Y-m-d')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
