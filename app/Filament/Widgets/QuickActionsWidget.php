<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Sidra's "إجراءات سريعة" card.
 *
 * The things an owner comes to this panel to CREATE are otherwise three
 * clicks away - sidebar group, resource, then the create button - and the
 * dashboard is the one screen he always lands on. This collapses that to
 * one click for the four he actually makes.
 *
 * Every action is permission-checked individually rather than the card as a
 * whole, so an editor who may write articles but not add services sees a
 * shorter card instead of a button that refuses him at the end of it.
 *
 * @phpstan-type QuickAction array{label: string, url: string, icon: string}
 */
class QuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return self::actionsFor() !== [];
    }

    /**
     * @return array{actions: list<QuickAction>}
     */
    protected function getViewData(): array
    {
        return ['actions' => self::actionsFor()];
    }

    /**
     * @return list<QuickAction>
     */
    private static function actionsFor(): array
    {
        $candidates = [
            ['صفحة جديدة', PageResource::class, 'create_page', 'heroicon-o-document-plus'],
            ['خدمة جديدة', ServiceResource::class, 'create_service', 'heroicon-o-sparkles'],
            ['مشروع جديد', ProjectResource::class, 'create_project', 'heroicon-o-briefcase'],
            ['مقال جديد', ArticleResource::class, 'create_article', 'heroicon-o-pencil-square'],
            ['رفع صورة', MediaResource::class, 'create_media', 'heroicon-o-photo'],
        ];

        $user = Auth::user();
        $actions = [];

        foreach ($candidates as [$label, $resource, $permission, $icon]) {
            if (! $user?->can($permission)) {
                continue;
            }

            // rescue(): a resource without a create page has no such route,
            // and a dashboard card must never be the reason the whole
            // dashboard fails to render.
            $url = rescue(fn (): ?string => $resource::getUrl('create'), null, false);

            if ($url === null) {
                continue;
            }

            $actions[] = ['label' => $label, 'url' => $url, 'icon' => $icon];
        }

        return $actions;
    }
}
