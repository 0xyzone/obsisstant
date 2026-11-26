<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\ObsStatsOverview;
use App\Filament\Widgets\ObsStudioWorkspaceWidget;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Obsisstant')
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
                NavigationGroup::make('Live Control')
                    ->collapsible(false),
                NavigationGroup::make('Stage & Overlays')
                    ->collapsible(false),
                NavigationGroup::make('Studio & Automation')
                    ->collapsible(false),
                NavigationGroup::make('Documentation')
                    ->collapsed(false),
            ])
            ->navigationItems([
                NavigationItem::make('Studio Control Center')
                    ->group('Live Control')
                    ->url('/studio')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->sort(1),
                NavigationItem::make('Mobile Deck')
                    ->group('Live Control')
                    ->url('/deck', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->sort(2),
                NavigationItem::make('Changelog')
                    ->group('Documentation')
                    ->url('/changelog', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->sort(2),
                NavigationItem::make('Public Showcase')
                    ->group('Documentation')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->sort(3),
                NavigationItem::make('Super Admin HQ')
                    ->group('Documentation')
                    ->url('/hq')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->visible(fn (): bool => (bool) auth()->user()?->is_super_admin)
                    ->sort(4),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ObsStatsOverview::class,
                ObsStudioWorkspaceWidget::class,
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
