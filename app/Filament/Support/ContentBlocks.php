<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
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

                Block::make('cta')
                    ->label('دعوة للعمل (CTA)')
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        TextInput::make('heading')->label('العنوان')->required(),
                        TextInput::make('button_label')->label('نص الزر')->required(),
                        TextInput::make('button_url')->label('رابط الزر')->url()->required(),
                    ])
                    ->columns(1),

                Block::make('faq')
                    ->label('الأسئلة الشائعة')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        TextInput::make('heading')->label('عنوان القسم'),
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
