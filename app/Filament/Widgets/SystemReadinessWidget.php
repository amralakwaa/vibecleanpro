<?php

namespace App\Filament\Widgets;

use App\Support\Readiness\LaunchReadiness;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Operational readiness at a glance: what works, what is configured but
 * off, and what still needs a decision - each with its reason and a link
 * to the screen that fixes it. States come from LaunchReadiness, which
 * computes them; no secret value is ever rendered.
 */
class SystemReadinessWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.system-readiness';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user && ($user->can('manage_business_profile') || $user->can('manage_site_settings'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['groups' => app(LaunchReadiness::class)->groups()];
    }
}
