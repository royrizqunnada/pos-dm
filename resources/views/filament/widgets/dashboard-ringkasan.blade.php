@php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
<x-filament-widgets::widget>
    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Ringkasan Hari Ini</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>

        {{-- KPI utama --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Hero: Margin --}}
            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-500/30 dark:bg-primary-500/10">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-primary-700 dark:text-primary-300">Margin Saya</p>
                    @if (! is_null($marginTrend))
                        <span @class([
                            'inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300' => $marginTrend >= 0,
                            'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300' => $marginTrend < 0,
                        ])>{{ $marginTrend >= 0 ? '▲' : '▼' }} {{ abs($marginTrend) }}%</span>
                    @endif
                </div>
                <p class="mt-2 text-3xl font-bold tracking-tight text-primary-700 dark:text-primary-200">{{ $rp($today['total_margin']) }}</p>
                <p class="mt-1 text-xs text-primary-600/70 dark:text-primary-300/70">vs kemarin {{ $rp($yesterday['total_margin']) }}</p>
            </div>

            @php($cards = [
                ['label' => 'Total Penjualan', 'value' => $rp($today['total_gross']), 'sub' => 'Dibayar ke vendor '.$rp($today['total_base_owed']), 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['label' => 'Transaksi', 'value' => number_format($today['order_count'], 0, ',', '.'), 'sub' => 'order lunas hari ini', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
                ['label' => 'Rata-rata / Transaksi', 'value' => $rp($avgPerTx), 'sub' => 'nilai belanja per nota', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
            ])
            @foreach ($cards as $c)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $c['label'] }}</p>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icon'] }}" /></svg>
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $c['value'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $c['sub'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-5">
            {{-- Bulan ini --}}
            <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-3.5 dark:border-white/5">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Bulan Ini ({{ now()->translatedFormat('F') }})</h3>
                </div>
                <dl class="divide-y divide-gray-50 px-5 dark:divide-white/5">
                    <div class="flex items-center justify-between py-3"><dt class="text-sm text-gray-500 dark:text-gray-400">Margin Saya</dt><dd class="text-base font-bold text-primary-600 dark:text-primary-400">{{ $rp($month['total_margin']) }}</dd></div>
                    <div class="flex items-center justify-between py-3"><dt class="text-sm text-gray-500 dark:text-gray-400">Total Penjualan</dt><dd class="text-base font-semibold text-gray-950 dark:text-white">{{ $rp($month['total_gross']) }}</dd></div>
                    <div class="flex items-center justify-between py-3"><dt class="text-sm text-gray-500 dark:text-gray-400">Dibayar ke Vendor</dt><dd class="text-base font-semibold text-gray-950 dark:text-white">{{ $rp($month['total_base_owed']) }}</dd></div>
                    <div class="flex items-center justify-between py-3"><dt class="text-sm text-gray-500 dark:text-gray-400">Transaksi</dt><dd class="text-base font-semibold text-gray-950 dark:text-white">{{ number_format($month['order_count'], 0, ',', '.') }}</dd></div>
                </dl>
            </div>

            {{-- Top vendor --}}
            <div class="lg:col-span-3 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-3.5 dark:border-white/5">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Top Vendor Hari Ini</h3>
                </div>
                <div class="space-y-3 px-5 py-4">
                    @forelse ($topVendors as $i => $vendor)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span @class([
                                        'flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-xs font-bold',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' => $i === 0,
                                        'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-200' => $i === 1,
                                        'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300' => $i === 2,
                                        'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' => $i > 2,
                                    ])>{{ $i + 1 }}</span>
                                    <span class="truncate font-medium text-gray-900 dark:text-white">{{ $vendor->name }}</span>
                                    <span class="shrink-0 text-xs text-gray-400">{{ number_format($vendor->qty, 0, ',', '.') }} item</span>
                                </div>
                                <span class="shrink-0 font-semibold text-gray-950 dark:text-white">{{ $rp($vendor->base_owed) }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/5">
                                <div class="h-full rounded-full bg-primary-500" style="width: {{ max(4, round($vendor->base_owed / $maxBase * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <svg class="mb-2 h-9 w-9 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                            <p class="text-sm font-medium text-gray-500">Belum ada penjualan hari ini.</p>
                            <p class="mt-1 text-xs text-gray-400">Data muncul setelah ada transaksi di kasir.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
