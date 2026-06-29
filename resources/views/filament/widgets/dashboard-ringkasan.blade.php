@php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
<x-filament-widgets::widget>
    <div class="space-y-5">
        {{-- ===== Hero: ringkasan hari ini (gaya aplikasi) ===== --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-600 to-primary-700 p-5 text-white shadow-lg shadow-primary-600/20 sm:p-6">
            {{-- ornamen --}}
            <div class="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute top-10 -right-2 h-24 w-24 rounded-full bg-white/5"></div>

            <div class="relative">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-white/80">Margin Saya <span class="text-white/60">· hari ini</span></p>
                    @if (! is_null($marginTrend))
                        <span @class([
                            'inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-semibold backdrop-blur',
                            'bg-white/20 text-white' => $marginTrend >= 0,
                            'bg-red-500/30 text-white' => $marginTrend < 0,
                        ])>{{ $marginTrend >= 0 ? '▲' : '▼' }} {{ abs($marginTrend) }}%</span>
                    @endif
                </div>
                <p class="mt-1 text-4xl font-bold tracking-tight sm:text-5xl">{{ $rp($today['total_margin']) }}</p>
                <p class="mt-1 text-xs text-white/70">{{ now()->translatedFormat('l, d F Y') }} · vs kemarin {{ $rp($yesterday['total_margin']) }}</p>

                {{-- sub-stat 2x2 --}}
                <div class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4">
                    @php($subs = [
                        ['label' => 'Total Penjualan', 'value' => $rp($today['total_gross'])],
                        ['label' => 'Transaksi', 'value' => number_format($today['order_count'], 0, ',', '.').' order'],
                        ['label' => 'Dibayar ke Vendor', 'value' => $rp($today['total_base_owed'])],
                        ['label' => 'Rata-rata / Transaksi', 'value' => $rp($avgPerTx)],
                    ])
                    @foreach ($subs as $sub)
                        <div>
                            <p class="text-xl font-bold tracking-tight sm:text-2xl">{{ $sub['value'] }}</p>
                            <p class="mt-0.5 text-xs text-white/70">{{ $sub['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- ringkasan bulan ini --}}
                <div class="mt-5 flex flex-col gap-1 border-t border-white/15 pt-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-white/80">Margin bulan ini (<span class="text-white/60">{{ now()->translatedFormat('F') }}</span>): <span class="font-bold text-white">{{ $rp($month['total_margin']) }}</span></span>
                    <span class="text-white/80">Penjualan: <span class="font-bold text-white">{{ $rp($month['total_gross']) }}</span></span>
                </div>
            </div>
        </div>

        {{-- ===== Top vendor hari ini ===== --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3.5 dark:border-white/5 sm:px-5">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Top Vendor Hari Ini</h3>
                <span class="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ $topVendors->count() }}</span>
            </div>
            <div class="space-y-3 px-4 py-4 sm:px-5">
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
</x-filament-widgets::widget>
