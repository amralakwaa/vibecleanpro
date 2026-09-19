<?php

namespace App\Filament\Resources\Media;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStage;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media as MediaModel;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                ->required(fn (Get $get): bool => $get('status') === MediaStatus::Ready->value)
                ->helperText('يُكتب مما تُظهره الصورة فعلًا — لا من اسم الملف. مطلوب قبل اعتماد الصورة.')
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('caption')->label('تعليق الصورة')->maxLength(255)->columnSpanFull(),
            Section::make('التحقق والنشر')
                ->description('صورة غير معتمدة لا تظهر في أي صفحة منشورة، ولا تُستخدم كدليل على عمل. الاعتماد ومراجعة الخصوصية من صلاحية مدير الوسائط.')
                ->schema([
                    Select::make('status')
                        ->label('حالة الصورة')
                        ->options(MediaStatus::options())
                        ->default(MediaStatus::Pending->value)
                        ->required()
                        ->live()
                        ->disabled(fn (): bool => ! static::canApprove()),
                    Select::make('privacy_status')
                        ->label('الخصوصية')
                        ->options(MediaPrivacyStatus::options())
                        ->default(MediaPrivacyStatus::Unverified->value)
                        ->required()
                        ->helperText('وجوه العمال أو العملاء، أرقام اللوحات، علامات تجارية لطرف ثالث: تبقى محجوبة حتى الموافقة.')
                        ->disabled(fn (): bool => ! static::canApprove()),
                    Select::make('media_type')
                        ->label('نوع الصورة')
                        ->options(MediaType::options())
                        ->default(MediaType::Real->value)
                        ->required(),
                    Select::make('captured_stage')
                        ->label('مرحلة التصوير')
                        ->options([MediaStage::Before->value => 'قبل', MediaStage::During->value => 'أثناء', MediaStage::After->value => 'بعد']),
                    Textarea::make('verified_description')
                        ->label('الوصف الموثّق')
                        ->helperText('ما تُظهره الصورة فعلًا، كما أكده من صوّرها. مطلوب لاعتماد صورة حقيقية.')
                        ->rows(2)
                        ->columnSpanFull(),
                    Select::make('service_id')->label('الخدمة')->relationship('service', 'name')->searchable()->preload(),
                    Select::make('area_id')->label('الحي')->relationship('area', 'name')->searchable()->preload(),
                    TextInput::make('consent_ref')->label('مرجع الموافقة')->maxLength(255),
                    DatePicker::make('captured_at')->label('تاريخ التصوير'),
                ])
                ->columns(2)
                ->columnSpanFull(),
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
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn (MediaStatus $state) => $state->label())
                    ->color(fn (MediaStatus $state) => $state->color()),
                TextColumn::make('privacy_status')->label('الخصوصية')
                    ->formatStateUsing(fn (MediaPrivacyStatus $state) => $state->label())
                    ->toggleable(),
                TextColumn::make('alt_text')->label('النص البديل')->searchable()->limit(40),
                TextColumn::make('size')->label('الحجم')->formatStateUsing(fn (?int $state) => $state ? Number::fileSize($state) : '—'),
                TextColumn::make('width')->label('الأبعاد')->formatStateUsing(fn ($state, MediaModel $record) => $record->width ? "{$record->width}×{$record->height}" : '—'),
                TextColumn::make('created_at')->label('تاريخ الرفع')->dateTime('Y-m-d')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(MediaStatus::options()),
                SelectFilter::make('privacy_status')->label('الخصوصية')->options(MediaPrivacyStatus::options()),
                SelectFilter::make('media_type')->label('النوع')->options(MediaType::options()),
                SelectFilter::make('service_id')->label('الخدمة')->relationship('service', 'name'),
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

    private static function canApprove(): bool
    {
        return Auth::user()?->can('approve_media') ?? false;
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
