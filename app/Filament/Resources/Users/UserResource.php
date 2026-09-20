<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Listeners\LogSecurityEvent;
use App\Models\User;
use App\Support\UserGuardRails;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                // Only a Super Admin may grant Super Admin: the role
                // bypasses every policy, so handing it out cannot be an
                // ordinary administrator's privilege.
                ->relationship('roles', 'name', fn ($query) => Auth::user()?->hasRole('Super Admin')
                    ? $query
                    : $query->where('name', '!=', 'Super Admin'))
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
                TextColumn::make('app_authentication_secret')
                    ->label('التحقق الثنائي')
                    ->badge()
                    // The stored secret is never rendered - only whether one exists.
                    ->state(fn (User $record): string => filled($record->app_authentication_secret) ? 'مفعّل' : 'غير مفعّل')
                    ->color(fn (string $state): string => $state === 'مفعّل' ? 'success' : 'gray'),
                TextColumn::make('last_login_at')->label('آخر دخول')->dateTime('Y-m-d H:i')->placeholder('لم يسجل الدخول')->sortable(),
                TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime('Y-m-d')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                Action::make('resetTwoFactor')
                    ->label('إعادة تعيين التحقق الثنائي')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->visible(fn (User $record): bool => filled($record->app_authentication_secret) && (Auth::user()?->can('update', $record) ?? false))
                    ->requiresConfirmation()
                    ->modalDescription('يُحذف مفتاح التحقق الثنائي ورموز الاسترداد لهذا المستخدم، وسيحتاج إلى إعداده من جديد عند الدخول. استخدمه عند فقدان الجهاز فقط.')
                    ->action(function (User $record): void {
                        $record->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
                        LogSecurityEvent::record('admin.two_factor_reset', Auth::user());
                        Notification::make()->success()->title('أُعيد تعيين التحقق الثنائي')->body('أبلغ المستخدم بإعداده مجددًا عند أول دخول.')->send();
                    }),
                Action::make('revokeSessions')
                    ->label('إنهاء الجلسات')
                    ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                    ->color('gray')
                    ->visible(fn (User $record): bool => Auth::user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription('تُنهى كل جلسات هذا المستخدم على جميع الأجهزة، وسيحتاج إلى تسجيل الدخول من جديد.')
                    ->action(function (User $record): void {
                        $deleted = DB::table('sessions')->where('user_id', $record->getKey())->delete();
                        LogSecurityEvent::record('admin.sessions_revoked', Auth::user());
                        Notification::make()->success()->title('أُنهيت الجلسات')->body("عدد الجلسات المُنهاة: {$deleted}.")->send();
                    }),
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
