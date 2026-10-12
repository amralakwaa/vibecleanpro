<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\AccountSecurity;
use App\Filament\Pages\Auth\Login;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\BunnyFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Vibe Clean Pro')
            // Inter, Filament's default, ships only a Latin subset and has
            // no Arabic glyphs, so every Arabic character in this panel was
            // rendering in an OS fallback that differed per device. Plex
            // Arabic carries both scripts and is what the public site uses.
            // Bunny is the same host the public layout already preconnects
            // to, so the panel adds no new third party.
            ->font('Tajawal', provider: BunnyFontProvider::class)
            // Sidra's panel is dark, and matching it is the point of this
            // theme. Dark is the DEFAULT rather than forced, so the mode
            // toggle in the user menu still works for anyone who wants the
            // light scheme; Filament's own dark palette is overridden by
            // the Sidra surface ramp in the theme file.
            ->defaultThemeMode(ThemeMode::Dark)
            ->brandLogo(fn (): View => view('filament.brand'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon-32x32.png'))
            ->login(Login::class)
            ->profile(AccountSecurity::class, isSimple: false)
            // Optional per user for now: requiring it would lock out every
            // current admin until they enrol, which is an owner decision.
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable()->brandName('Vibe Clean Pro'),
            ], isRequired: false)
            // Mirrors the public design system in resources/css/app.css:
            // blue is the ACTION color, green is reserved for positive and
            // WhatsApp states and never competes with it as a primary fill.
            // `gray` is restated as the cool ramp so Filament's generated
            // component shades match the panel theme's surfaces.
            ->colors([
                'primary' => Color::hex('#2878b4'),
                'danger' => Color::hex('#dc2626'),
                'success' => Color::hex('#047857'),
                'warning' => Color::hex('#d97706'),
                'info' => Color::hex('#2563eb'),
                'gray' => Color::Gray,
            ])
            // Order is deliberate: the daily editing work first, money
            // second, configuration last. Every group a resource declares
            // must appear here - an undeclared group is appended in
            // discovery order, which is effectively random.
            ->navigationGroups([
                'المحتوى',
                'الوسائط',
                'SEO',
                'المبيعات',
                'الثقة والاعتمادات',
                'الإعدادات',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // Discovery already registers every widget in app/Filament/
            // Widgets, so this list only needs to NOT contain Filament's
            // AccountWidget. A card that greets you by name and offers a
            // logout arrow is the first thing on the page and the least
            // useful thing on it; the same logout lives in the user menu.
            // Each widget sets its own $sort, and together those values
            // make the page read top to bottom as one argument: what is
            // happening, who is waiting, what the content looks like, then
            // the operational detail.
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
