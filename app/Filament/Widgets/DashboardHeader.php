<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\RekapPeriode;
use App\Filament\Pages\TutupHari;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Widgets\Widget;

class DashboardHeader extends Widget
{
    protected string $view = 'filament.widgets.dashboard-header';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $hour = (int) now()->format('H');

        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        $user = auth()->user();
        $name = trim(explode(' ', (string) ($user?->name ?? 'Owner'))[0]) ?: 'Owner';

        return [
            'greeting' => $greeting,
            'name' => $name,
            'date' => now()->translatedFormat('l, d F Y'),
            'actions' => [
                [
                    'label' => 'Buka Kasir',
                    'url' => route('kasir'),
                    'navigate' => false,
                    'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
                    'accent' => true,
                ],
                [
                    'label' => 'Tutup Hari',
                    'url' => TutupHari::getUrl(),
                    'navigate' => true,
                    'icon' => 'M2.25 18.75a60.07 60.07 0 0 1 15.8 2.1c.72.2 1.45-.34 1.45-1.1v-1.05M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.38c0-.62.5-1.12 1.13-1.12H20.25M2.25 6v9m18-10.5v.75c0 .41.34.75.75.75h.75m-1.5-1.5h.38c.62 0 1.12.5 1.12 1.13v9.75c0 .62-.5 1.12-1.12 1.12h-.38m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.38a1.13 1.13 0 0 1-1.12-1.13V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                    'accent' => false,
                ],
                [
                    'label' => 'Rekap',
                    'url' => RekapPeriode::getUrl(),
                    'navigate' => true,
                    'icon' => 'M3 13.13c0-.63.5-1.13 1.13-1.13h2.25c.62 0 1.12.5 1.12 1.13v6.74c0 .63-.5 1.13-1.12 1.13H4.13C3.5 21 3 20.5 3 19.87v-6.74ZM9.75 8.63c0-.63.5-1.13 1.13-1.13h2.25c.62 0 1.12.5 1.12 1.13v11.25c0 .62-.5 1.12-1.12 1.12h-2.25a1.13 1.13 0 0 1-1.13-1.12V8.63ZM16.5 4.13c0-.63.5-1.13 1.13-1.13h2.25C20.5 3 21 3.5 21 4.13v15.75c0 .62-.5 1.12-1.12 1.12h-2.25a1.13 1.13 0 0 1-1.13-1.12V4.13Z',
                    'accent' => false,
                ],
                [
                    'label' => 'Pesanan',
                    'url' => OrderResource::getUrl(),
                    'navigate' => true,
                    'icon' => 'M2.25 3h1.39c.51 0 .96.34 1.09.84l.82 3.32m0 0 1.7 6.93c.13.5.58.84 1.09.84h9.4c.5 0 .94-.33 1.08-.82l1.85-6.51a.75.75 0 0 0-.72-.96H5.54m0 0L5.1 4.34M7.5 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm11.25 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
                    'accent' => false,
                ],
            ],
        ];
    }
}
