<x-filament-panels::page>
    @php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
    @php($totals = $this->totals)

    {{-- Filter --}}
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:flex-row sm:items-end">
        <div class="w-full sm:w-64">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi</label>
            <select wire:model.live="locationId" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800">
                @foreach ($this->getLocationOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-56">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
            <input type="date" wire:model.live="date" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800" />
        </div>
    </div>

    {{-- Ringkasan KPI --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @php($kpis = [
            ['label' => 'Total Transaksi', 'value' => number_format($totals['order_count'], 0, ',', '.'), 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z', 'accent' => false],
            ['label' => 'Total Uang Masuk', 'value' => $rp($totals['total_gross']), 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'accent' => false],
            ['label' => 'Dibayar ke Vendor', 'value' => $rp($totals['total_base_owed']), 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z', 'accent' => false],
            ['label' => 'Margin Saya (Hari Ini)', 'value' => $rp($totals['total_margin']), 'icon' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941', 'accent' => true],
        ])
        @foreach ($kpis as $kpi)
            <div @class([
                'rounded-2xl border p-4 shadow-sm sm:p-5',
                'border-primary-200 bg-primary-50 dark:border-primary-500/30 dark:bg-primary-500/10' => $kpi['accent'],
                'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900' => ! $kpi['accent'],
            ])>
                <div class="flex items-center justify-between gap-2">
                    <p @class(['min-w-0 truncate text-xs font-medium sm:text-sm', 'text-primary-700 dark:text-primary-300' => $kpi['accent'], 'text-gray-500 dark:text-gray-400' => ! $kpi['accent']])>{{ $kpi['label'] }}</p>
                    <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-lg sm:h-8 sm:w-8', 'bg-primary-100 text-primary-600 dark:bg-primary-500/20 dark:text-primary-300' => $kpi['accent'], 'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500' => ! $kpi['accent']])>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icon'] }}" /></svg>
                    </span>
                </div>
                <p @class(['mt-1.5 text-xl font-bold tracking-tight sm:mt-2 sm:text-2xl', 'text-primary-700 dark:text-primary-200' => $kpi['accent'], 'text-gray-900 dark:text-white' => ! $kpi['accent']])>{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tabel per vendor --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3.5 dark:border-white/5 sm:px-5">
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rincian per Vendor</h3>
            <span class="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ $this->rows->count() }} vendor</span>
        </div>

        {{-- Tampilan MOBILE: kartu bertumpuk (anti-terpotong) --}}
        <div class="divide-y divide-gray-100 dark:divide-white/5 sm:hidden">
            @forelse ($this->rows as $row)
                <div class="px-4 py-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex shrink-0 items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $row['code'] }}</span>
                            <span class="truncate font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</span>
                        </div>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ number_format($row['order_count'], 0, ',', '.') }} trx</span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg bg-gray-50 py-2 dark:bg-white/5">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Dibayar</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $rp($row['total_base_owed']) }}</p>
                        </div>
                        <div class="rounded-lg bg-primary-50 py-2 dark:bg-primary-500/10">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-primary-500/80">Margin</p>
                            <p class="mt-0.5 text-sm font-bold text-primary-600 dark:text-primary-400">{{ $rp($row['total_margin']) }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 py-2 dark:bg-white/5">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Total</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $rp($row['total_gross']) }}</p>
                        </div>
                    </div>
                    @if ($row['payout_account'])
                        <p class="mt-2 truncate text-xs text-gray-400">Rekening: {{ $row['payout_account'] }}</p>
                    @endif
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <svg class="mx-auto mb-2 h-9 w-9 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    <p class="text-sm font-medium text-gray-500">Belum ada transaksi lunas pada tanggal ini.</p>
                    <p class="text-xs text-gray-400">Data muncul otomatis setelah ada pembayaran di kasir.</p>
                </div>
            @endforelse
            @if ($this->rows->isNotEmpty())
                <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-white/5">
                    <span class="text-sm font-bold text-gray-900 dark:text-white">TOTAL ({{ number_format($totals['order_count'], 0, ',', '.') }} trx)</span>
                    <span class="text-sm font-bold text-primary-700 dark:text-primary-300">{{ $rp($totals['total_margin']) }}</span>
                </div>
            @endif
        </div>

        {{-- Tampilan DESKTOP: tabel penuh --}}
        <div class="hidden overflow-x-auto sm:block">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/5">
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 sm:px-5">Vendor</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400 sm:px-5">Trx</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400 sm:px-5">Dibayar</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400 sm:px-5">Margin</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400 sm:px-5">Total Kotor</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 sm:px-5">Rekening</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                @forelse ($this->rows as $row)
                    <tr class="transition hover:bg-gray-50/70 dark:hover:bg-white/5">
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $row['code'] }}</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300 sm:px-5">{{ number_format($row['order_count'], 0, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white sm:px-5">{{ $rp($row['total_base_owed']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-primary-600 dark:text-primary-400 sm:px-5">{{ $rp($row['total_margin']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700 dark:text-gray-300 sm:px-5">{{ $rp($row['total_gross']) }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 sm:px-5">{{ $row['payout_account'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <svg class="mx-auto mb-2 h-9 w-9 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <p class="text-sm font-medium text-gray-500">Belum ada transaksi lunas pada tanggal ini.</p>
                            <p class="text-xs text-gray-400">Data muncul otomatis setelah ada pembayaran di kasir.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($this->rows->isNotEmpty())
                <tfoot>
                    <tr class="border-t-2 border-gray-200 bg-gray-50 font-bold text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <td class="px-4 py-3 sm:px-5">TOTAL</td>
                        <td class="px-4 py-3 text-right sm:px-5">{{ number_format($totals['order_count'], 0, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right sm:px-5">{{ $rp($totals['total_base_owed']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-primary-700 dark:text-primary-300 sm:px-5">{{ $rp($totals['total_margin']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right sm:px-5">{{ $rp($totals['total_gross']) }}</td>
                        <td class="px-4 py-3 sm:px-5"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
        </div>
    </div>
</x-filament-panels::page>
