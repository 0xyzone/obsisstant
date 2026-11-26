<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Services\ReverbStatusService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('hq')
            ->brandName('Obsisstant HQ')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): string => asset(app()->isProduction() ? 'favicon-prod.svg' : 'favicon-local.svg'))
            ->login(Login::class)
            ->darkMode(true, isForced: true)
            ->font('Poppins')
            ->monoFont('Poppins')
            ->broadcasting(static fn (): bool => ReverbStatusService::isConfigured())
            ->maxContentWidth('full')
            ->sidebarWidth('16.5rem')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::hex('#CDAD69'),
                'amber' => Color::hex('#F4E185'),
                'gray' => Color::hex('#292C31'),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.components.obsidian-styles'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('components.dev-hot-reload'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('filament.components.obsidian-backdrop'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn () => view('filament.components.login-hero'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.components.sidebar-version'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => view('filament.components.reverb-status'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn () => view('filament.components.simple-topbar'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.components.mobile-bottom-dock'),
            )
            ->navigationGroups([
                NavigationGroup::make('SaaS Management')
                    ->collapsible(false),
                NavigationGroup::make('Workspace')
                    ->collapsible(false),
            ])
            ->navigationItems([
                NavigationItem::make('Studio Admin Panel')
                    ->group('Workspace')
                    ->url('/admin')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->sort(1),
                NavigationItem::make('Public Showcase')
                    ->group('Workspace')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->sort(2),
            ])
            ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\Filament\SuperAdmin\Resources')
            ->pages([
                Dashboard::class,
            ])
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
