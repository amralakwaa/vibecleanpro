<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Resources\Redirects\Pages\CreateRedirect;
use App\Filament\Resources\Redirects\Pages\EditRedirect;
use App\Filament\Resources\Redirects\Pages\ListRedirects;
use App\Models\Redirect;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
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
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $recordTitleAttribute = 'from_path';

    protected static ?string $modelLabel = 'تحويل (Redirect)';

    protected static ?string $pluralModelLabel = 'التحويلات (Redirects)';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')
                ->label('المسار القديم (Source)')
                ->required()
                ->startsWith('/')
                ->unique(ignoreRecord: true)
                ->helperText('مثال: /old-page')
                ->maxLength(255),
            TextInput::make('to_path')
                ->label('المسار الجديد (Destination)')
                ->required()
                ->different('from_path')
                ->maxLength(255)
                ->rules([
                    fn (?Redirect $record) => function (string $attribute, $value, Closure $fail) use ($record) {
                        $existsAsSource = Redirect::query()
                            ->where('from_path', $value)
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($existsAsSource) {
                            $fail('هذا المسار مُستخدم بالفعل كمصدر لتحويل آخر. ربطه هنا سينشئ سلسلة تحويلات (Redirect Chain) - عدّل التحويل الآخر ليشير مباشرة للوجهة النهائية بدلًا من ذلك.');
                        }
                    },
                ]),
            Select::make('type')
                ->label('نوع التحويل')
                ->options([301 => '301 - دائم', 302 => '302 - مؤقت'])
                ->default(301)
                ->required(),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_path')->label('من')->searchable()->copyable(),
                TextColumn::make('to_path')->label('إلى')->searchable()->copyable(),
                TextColumn::make('type')->label('النوع')->badge(),
                IconColumn::make('is_active')->label('مفعّل')->boolean(),
                TextColumn::make('hits')->label('عدد الاستخدامات')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => ListRedirects::route('/'),
            'create' => CreateRedirect::route('/create'),
            'edit' => EditRedirect::route('/{record}/edit'),
        ];
    }
}
