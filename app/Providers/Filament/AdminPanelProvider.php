<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Widgets\DashboardHeader;
use App\Filament\Widgets\DashboardRingkasan;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\GrafikPenjualan;
use Filament\Enums\ThemeMode;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('DM Kuliner POS')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/dm-kuliner-logo.png'))
            ->defaultThemeMode(ThemeMode::Light)
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                // Orange DM Kuliner (selaras logo). Shade gelap oranye-kecoklatan
                // supaya teks putih di tombol tetap terbaca.
                'primary' => [
                    50 => '#fdf6ec', 100 => '#f9e6c6', 200 => '#f3cd89', 300 => '#eeb14e',
                    400 => '#e9971f', 500 => '#d97706', 600 => '#b45309', 700 => '#92400e',
                    800 => '#78350f', 900 => '#633012', 950 => '#3f1e0a',
                ],
                'gray' => Color::Stone, // netral hangat, cocok dengan coklat/orange
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                DashboardHeader::class,
                DashboardStats::class,
                GrafikPenjualan::class,
                DashboardRingkasan::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.bottom-nav')->render(),
            )
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
