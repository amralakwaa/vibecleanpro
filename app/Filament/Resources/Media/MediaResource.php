<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media as MediaModel;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = MediaModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'الوسائط';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'original_filename';

    protected static ?string $modelLabel = 'وسائط';

    protected static ?string $pluralModelLabel = 'مكتبة الوسائط';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('الملف')
                ->image()
                ->disk('public')
                ->directory('media')
                ->imagePreviewHeight('150')
                ->maxSize(5120)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }

                    $set('original_filename', $state->getClientOriginalName());
                    $set('mime_type', $state->getMimeType());
                    $set('size', $state->getSize());

                    $dimensions = @getimagesize($state->getRealPath());

                    if ($dimensions) {
                        $set('width', $dimensions[0]);
                        $set('height', $dimensions[1]);
                    }
                })
                ->columnSpanFull(),
            TextInput::make('alt_text')
                ->label('النص البديل (Alt Text)')
                ->required()
                ->helperText('مطلوب لإتاحة الوصول ولمحركات البحث.')
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('caption')->label('تعليق الصورة')->maxLength(255)->columnSpanFull(),
            Hidden::make('original_filename'),
            Hidden::make('mime_type'),
            Hidden::make('size'),
            Hidden::make('width'),
            Hidden::make('height'),
            Hidden::make('disk')->default('public'),
            Hidden::make('uploaded_by')->default(fn () => Auth::id()),
            Placeholder::make('meta')
                ->label('بيانات الملف')
                ->content(fn (?MediaModel $record) => $record
                    ? sprintf('%s · %s × %s · %s', $record->mime_type, $record->width, $record->height, Number::fileSize($record->size ?? 0))
                    : '—')
                ->visible(fn (?MediaModel $record) => $record !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')->label('')->disk('public')->square(),
                TextColumn::make('original_filename')->label('الملف')->searchable(),
                TextColumn::make('alt_text')->label('النص البديل')->searchable()->limit(40),
                TextColumn::make('size')->label('الحجم')->formatStateUsing(fn (?int $state) => $state ? Number::fileSize($state) : '—'),
                TextColumn::make('width')->label('الأبعاد')->formatStateUsing(fn ($state, MediaModel $record) => $record->width ? "{$record->width}×{$record->height}" : '—'),
                TextColumn::make('created_at')->label('تاريخ الرفع')->dateTime('Y-m-d')->sortable(),
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
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
