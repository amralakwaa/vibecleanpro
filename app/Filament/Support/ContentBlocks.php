<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/**
 * The Page Builder used inside a Page's "content_blocks" field. Core block
 * set only for this phase - architecture leaves room to add more Block::make()
 * entries later without changing anything else.
 *
 * "faq" and "related_content" deliberately carry no real content of their
 * own: FAQs live in the faqs table (page_id relation) and related links in
 * internal_links, so a block here is just a placement marker with an
 * optional heading - never a second copy of that data.
 */
class ContentBlocks
{
    public static function field(string $name = 'contentBlocks'): Builder
    {
        return Builder::make($name)
            ->label('أقسام الصفحة')
            ->addActionLabel('إضافة قسم')
            ->blocks([
                Block::make('hero')
                    ->label('العنوان الرئيسي (Hero)')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        TextInput::make('heading')->label('العنوان')->required(),
                        Textarea::make('subheading')->label('العنوان الفرعي')->rows(2),
                        MediaPicker::make('background_media_id', 'صورة الخلفية'),
                        TextInput::make('cta_label')->label('نص زر الدعوة للعمل'),
                        TextInput::make('cta_url')->label('رابط الزر')->url(),
                    ])
                    ->columns(2),

                Block::make('rich_text')
                    ->label('نص منسق')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        RichEditor::make('content')->label('المحتوى')->required(),
                    ]),

                Block::make('image')
                    ->label('صورة')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        MediaPicker::make('media_id', 'الصورة')->required(),
                        TextInput::make('caption')->label('تعليق الصورة'),
                    ]),

                Block::make('gallery')
                    ->label('معرض صور')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        MediaPicker::makeMultiple('media_ids', 'الصور')->required(),
                    ]),

                Block::make('features')
                    ->label('مزايا')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم'),
                        Repeater::make('items')
                            ->label('المزايا')
                            ->schema([
                                TextInput::make('icon')->label('أيقونة (اسم Heroicon)'),
                                TextInput::make('title')->label('العنوان')->required(),
                                Textarea::make('description')->label('الوصف')->rows(2),
                            ])
                            ->columns(3)
                            ->defaultItems(1),
                    ]),

                Block::make('steps')
                    ->label('خطوات')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم'),
                        Repeater::make('items')
                            ->label('الخطوات')
                            ->schema([
                                TextInput::make('title')->label('عنوان الخطوة')->required(),
                                Textarea::make('description')->label('الوصف')->rows(2),
                            ])
                            ->defaultItems(1),
                    ]),

                Block::make('inclusions')
                    ->label('ما تشمله الخدمة / ما لا تشمله')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم')->placeholder('ماذا تشمل الخدمة؟'),
                        Repeater::make('included')
                            ->label('مشمول')
                            ->simple(TextInput::make('item')->label('عنصر')->required()->maxLength(160))
                            ->addActionLabel('إضافة عنصر')
                            ->defaultItems(0),
                        Repeater::make('excluded')
                            ->label('غير مشمول')
                            ->simple(TextInput::make('item')->label('عنصر')->required()->maxLength(160))
                            ->addActionLabel('إضافة عنصر')
                            ->defaultItems(0),
                    ])
                    ->columns(1),

                Block::make('price_factors')
                    ->label('ما الذي يحدد السعر؟')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم')->placeholder('ما الذي يحدد السعر؟'),
                        Repeater::make('items')
                            ->label('العوامل')
                            ->schema([
                                TextInput::make('title')->label('العامل')->required()->maxLength(120),
                                Textarea::make('description')->label('كيف يؤثر')->rows(2)->maxLength(300),
                            ])
                            ->addActionLabel('إضافة عامل')
                            ->defaultItems(0),
                        Textarea::make('note')->label('ملاحظة')->rows(2)->maxLength(300)->helperText('مثل: السعر النهائي بعد المعاينة.'),
                    ])
                    ->columns(1),

                Block::make('packages')
                    ->label('باقات الخدمة')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم'),
                        Repeater::make('items')
                            ->label('الباقات')
                            ->schema([
                                TextInput::make('name')->label('اسم الباقة')->required(),
                                TextInput::make('variant')->label('المساحة/الكمية/النوع'),
                                TextInput::make('price')->label('السعر (ريال)')->numeric()->required(),
                                TextInput::make('previous_price')->label('السعر السابق (عند خصم حقيقي فقط)')->numeric(),
                                Textarea::make('included_items')->label('العناصر المشمولة (سطر لكل عنصر)')->rows(3),
                                TextInput::make('cta_url')->label('رابط الدعوة للعمل')->url(),
                            ])
                            ->columns(2)
                            ->defaultItems(1),
                    ]),

                Block::make('cta')
                    ->label('دعوة للعمل (CTA)')
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        TextInput::make('heading')->label('العنوان')->required(),
                        TextInput::make('button_label')->label('نص الزر')->required(),
                        TextInput::make('button_url')->label('رابط الزر')->url()->required(),
                    ])
                    ->columns(1),

                // `source` is the editor's explicit choice: a page renders
                // its own FAQs by default; only a block set to "sitewide"
                // (the FAQ hub) pulls the global page_id = null pool in.
                Block::make('faq')
                    ->label('الأسئلة الشائعة')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم'),
                        Select::make('source')
                            ->label('مصدر الأسئلة')
                            ->options([
                                'page' => 'أسئلة هذه الصفحة فقط (من تبويب الأسئلة الشائعة)',
                                'sitewide' => 'الأسئلة العامة للموقع (لصفحة الأسئلة الشائعة الرئيسية)',
                            ])
                            ->default('page')
                            ->native(false),
                    ])
                    ->columns(1),

                Block::make('related_content')
                    ->label('محتوى ذو صلة')
                    ->icon('heroicon-o-link')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم'),
                    ])
                    ->columns(1),
            ])
            ->collapsible()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}
