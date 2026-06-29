@auth
    @php
        $u = auth()->user();
        $isAdmin = $u?->hasAnyRole(['owner', 'manager']) ?? false;

        $dashboardUrl = \Filament\Pages\Dashboard::getUrl();
        $tutupUrl = \App\Filament\Pages\TutupHari::getUrl();
        $rekapUrl = \App\Filament\Pages\RekapPeriode::getUrl();
        $ordersUrl = \App\Filament\Resources\Orders\OrderResource::getUrl();

        $current = request()->url();
        $isActive = fn (string $url, bool $exact = false) => $exact
            ? rtrim($current, '/') === rtrim($url, '/')
            : str_starts_with(rtrim($current, '/'), rtrim($url, '/'));

        $items = [];
        if ($isAdmin) {
            $items = [
                ['label' => 'Beranda', 'url' => $dashboardUrl, 'exact' => true, 'icon' => 'M2.25 12 11.2 3.05a1.13 1.13 0 0 1 1.6 0L21.75 12M4.5 9.75v10.13c0 .62.5 1.12 1.13 1.12H9.75v-4.87c0-.63.5-1.13 1.13-1.13h2.24c.63 0 1.13.5 1.13 1.13V21h4.12c.63 0 1.13-.5 1.13-1.12V9.75M8.25 21h8.25'],
                ['label' => 'Pesanan', 'url' => $ordersUrl, 'exact' => false, 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
                ['label' => 'Tutup Hari', 'url' => $tutupUrl, 'exact' => false, 'icon' => 'M2.25 18.75a60.07 60.07 0 0 1 15.8 2.1c.72.2 1.45-.34 1.45-1.1v-1.05M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.38c0-.62.5-1.12 1.13-1.12H20.25M2.25 6v9m18-10.5v.75c0 .41.34.75.75.75h.75m-1.5-1.5h.38c.62 0 1.12.5 1.12 1.13v9.75c0 .62-.5 1.12-1.12 1.12h-.38m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.38a1.13 1.13 0 0 1-1.12-1.13V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
                ['label' => 'Rekap', 'url' => $rekapUrl, 'exact' => false, 'icon' => 'M3 13.13c0-.63.5-1.13 1.13-1.13h2.25c.62 0 1.12.5 1.12 1.13v6.74c0 .63-.5 1.13-1.12 1.13H4.13C3.5 21 3 20.5 3 19.87v-6.74ZM9.75 8.63c0-.63.5-1.13 1.13-1.13h2.25c.62 0 1.12.5 1.12 1.13v11.25c0 .62-.5 1.12-1.12 1.12h-2.25a1.13 1.13 0 0 1-1.13-1.12V8.63ZM16.5 4.13c0-.63.5-1.13 1.13-1.13h2.25C20.5 3 21 3.5 21 4.13v15.75c0 .62-.5 1.12-1.12 1.12h-2.25a1.13 1.13 0 0 1-1.13-1.12V4.13Z'],
            ];
        }
    @endphp

    @if (! empty($items))
        <nav class="dm-bottom-nav lg:hidden" aria-label="Navigasi utama">
            <div class="dm-bottom-nav__inner">
                @foreach ($items as $item)
                    @php($active = $isActive($item['url'], $item['exact']))
                    <a href="{{ $item['url'] }}" wire:navigate
                        @class(['dm-bottom-nav__item', 'dm-bottom-nav__item--active' => $active])>
                        <svg class="dm-bottom-nav__icon" fill="none" viewBox="0 0 24 24" stroke-width="{{ $active ? 2 : 1.6 }}" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="dm-bottom-nav__label">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <button type="button" x-data x-on:click="$store.sidebar.open()" class="dm-bottom-nav__item">
                    <svg class="dm-bottom-nav__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <span class="dm-bottom-nav__label">Menu</span>
                </button>
            </div>
        </nav>
    @endif
@endauth
