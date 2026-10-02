<?php

namespace App\Filament\Resources\Credentials\Schemas;

use App\Enums\CredentialStatus;
use App\Enums\CredentialType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CredentialForm
{
    public static function configure(Schema $schema): Schema
    {
        $typeOptions = collect(CredentialType::cases())->mapWithKeys(fn (CredentialType $t) => [$t->value => $t->label()])->all();
        $statusOptions = collect(CredentialStatus::cases())->mapWithKeys(fn (CredentialStatus $s) => [$s->value => $s->label()])->all();

        return $schema
            ->components([
                Section::make('الأساسيات')
                    ->schema([
                        TextInput::make('name_ar')->label('الاسم (عربي)')->required()->maxLength(255),
                        TextInput::make('name_en')->label('الاسم (إنجليزي)')->maxLength(255),
                        TextInput::make('slug')->label('المُعرِّف (slug)')->required()->unique(ignoreRecord: true)->maxLength(255),
                        Select::make('credential_type')->label('النوع')->options($typeOptions)->required()->live(),
                        Select::make('status')->label('الحالة')->options($statusOptions)->required()
                            ->helperText('يظهر للجمهور فقط: مُعتمد داخليًا (للمعايير الداخلية) أو موثّق (للخارجية). المخطّط/قيد الإصدار يبقى داخليًا.'),
                        TextInput::make('issuer')->label('الجهة المُصدِرة')->default('Vibe Clean Pro')->maxLength(255),
                    ])->columns(2),

                Section::make('الترميز والتواريخ')
                    ->schema([
                        TextInput::make('document_code')->label('رمز الوثيقة الداخلي')->placeholder('VCP-QMS-001')
                            ->unique(ignoreRecord: true)->maxLength(255)
                            ->helperText('للمعايير الداخلية — يُستخدم في رابط التحقق والطباعة.'),
                        TextInput::make('credential_number')->label('رقم الاعتماد/الترخيص الخارجي')->maxLength(255)
                            ->helperText('رقم رسمي حقيقي فقط — لا يُخترَع. يُترك فارغًا حتى توفر الوثيقة.'),
                        TextInput::make('version')->label('الإصدار')->placeholder('1.0')->maxLength(50),
                        DatePicker::make('issued_at')->label('تاريخ الإصدار')->native(false),
                        DatePicker::make('review_at')->label('المراجعة القادمة')->native(false),
                        DatePicker::make('expires_at')->label('تاريخ الانتهاء')->native(false),
                    ])->columns(3),

                Section::make('المحتوى')
                    ->schema([
                        Textarea::make('summary_ar')->label('ملخّص (عربي)')->rows(2)->columnSpanFull(),
                        Textarea::make('summary_en')->label('ملخّص (إنجليزي)')->rows(2)->columnSpanFull(),
                        Textarea::make('scope')->label('النطاق')->rows(2)->columnSpanFull(),
                        RichEditor::make('body_ar')->label('نص الوثيقة (عربي)')->columnSpanFull()
                            ->helperText('محتوى المعيار كاملًا: الغرض، النطاق، المسؤوليات، الإجراءات، المراجعة، الاعتماد.'),
                    ]),

                Section::make('العرض والوسائط')
                    ->schema([
                        Toggle::make('is_internal')->label('داخلي (صادر عن فايب كلين برو)')
                            ->default(fn ($get) => CredentialType::tryFrom((string) $get('credential_type'))?->isInternalByDefault() ?? false),
                        Toggle::make('is_public')->label('ظاهر للجمهور')
                            ->helperText('مع الحالة المناسبة فقط (مُعتمد/موثّق) يظهر فعلًا.'),
                        TextInput::make('icon')->label('أيقونة')->placeholder('badge-check')->maxLength(50),
                        TextInput::make('verification_url')->label('رابط تحقق خارجي')->url()->maxLength(255)
                            ->helperText('رابط تحقق الجهة المُصدِرة للاعتماد الخارجي، إن وُجد.'),
                        Select::make('document_media_id')->label('ملف الوثيقة')->relationship('documentMedia', 'path')->searchable()->preload(),
                        Select::make('logo_media_id')->label('شعار الجهة')->relationship('logoMedia', 'path')->searchable()->preload(),
                        TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0),
                    ])->columns(2),

                Section::make('ملاحظات داخلية')
                    ->schema([
                        Textarea::make('notes')->label('ملاحظات (لا تظهر للجمهور)')->rows(2)->columnSpanFull(),
                    ])->collapsed(),
            ]);
    }
}
