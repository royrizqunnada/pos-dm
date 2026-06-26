@php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
<x-filament-widgets::widget>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Ringkasan Hari Ini</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
        </div>

        {{-- KPI utama --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Hero: Margin --}}
            <div class="rounded-xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-500/30 dark:bg-primary-500/10">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-primary-700 dark:text-primary-300">Margin Saya</p>
                    @if (! is_null($marginTrend))
                        <span @class([
                            'inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300' => $marginTrend >= 0,
                            'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300' => $marginTrend < 0,
                        ])>
                            {{ $marginTrend >= 0 ? '▲' : '▼' }} {{ abs($marginTrend) }}%
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-3xl font-bold tracking-tight text-primary-700 dark:text-primary-200">{{ $rp($today['total_margin']) }}</p>
                <p class="mt-1 text-xs text-primary-600/70 dark:text-primary-300/70">vs kemarin {{ $rp($yesterday['total_margin']) }}</p>
            </div>

            {{-- Penjualan --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Penjualan</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $rp($today['total_gross']) }}</p>
                <p class="mt-1 text-xs text-gray-400">Dibayar ke vendor {{ $rp($today['total_base_owed']) }}</p>
            </div>

            {{-- Transaksi --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ number_format($today['order_count'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-400">order lunas hari ini</p>
            </div>

            {{-- Rata-rata --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rata-rata / Transaksi</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $rp($avgPerTx) }}</p>
                <p class="mt-1 text-xs text-gray-400">nilai belanja per nota</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            {{-- Bulan ini --}}
            <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Bulan Ini ({{ now()->translatedFormat('F') }})</h3>
                <dl class="mt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Margin Saya</dt>
                        <dd class="text-base font-bold text-primary-600 dark:text-primary-400">{{ $rp($month['total_margin']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-white/5">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Total Penjualan</dt>
                        <dd class="text-base font-semibold text-gray-950 dark:text-white">{{ $rp($month['total_gross']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-white/5">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Dibayar ke Vendor</dt>
                        <dd class="text-base font-semibold text-gray-950 dark:text-white">{{ $rp($month['total_base_owed']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-white/5">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Transaksi</dt>
                        <dd class="text-base font-semibold text-gray-950 dark:text-white">{{ number_format($month['order_count'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Top vendor --}}
            <div class="lg:col-span-3 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Top Vendor Hari Ini</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($topVendors as $i => $vendor)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-gray-100 text-xs font-bold text-gray-500 dark:bg-white/10 dark:text-gray-300">{{ $i + 1 }}</span>
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
                            <p class="text-sm text-gray-400">Belum ada penjualan hari ini.</p>
                            <p class="mt-1 text-xs text-gray-400">Data akan muncul setelah ada transaksi di kasir.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
