<x-filament-panels::page>
    @php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
    @php($totals = $this->totals)

    {{-- Filter --}}
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-56">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi</label>
                <select wire:model.live="locationId" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800">
                    @foreach ($this->getLocationOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-44">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari</label>
                <input type="date" wire:model.live="from" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800" />
            </div>
            <div class="w-full sm:w-44">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai</label>
                <input type="date" wire:model.live="to" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800" />
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @foreach (['today' => 'Hari ini', '7' => '7 hari', '30' => '30 hari', 'month' => 'Bulan ini', 'lastmonth' => 'Bulan lalu'] as $key => $label)
                <button wire:click="setPreset('{{ $key }}')" class="rounded-full border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5">{{ $label }}</button>
            @endforeach
            <span class="ml-auto inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ $this->dayCount }} hari</span>
        </div>
    </div>

    {{-- Ringkasan KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php($kpis = [
            ['label' => 'Total Transaksi', 'value' => number_format($totals['order_count'], 0, ',', '.'), 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z', 'accent' => false],
            ['label' => 'Total Penjualan', 'value' => $rp($totals['total_gross']), 'icon' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z', 'accent' => false],
            ['label' => 'Dibayar ke Vendor', 'value' => $rp($totals['total_base_owed']), 'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z', 'accent' => false],
            ['label' => 'Margin Saya', 'value' => $rp($totals['total_margin']), 'icon' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941', 'accent' => true],
        ])
        @foreach ($kpis as $kpi)
            <div @class([
                'rounded-2xl border p-5 shadow-sm',
                'border-primary-200 bg-primary-50 dark:border-primary-500/30 dark:bg-primary-500/10' => $kpi['accent'],
                'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900' => ! $kpi['accent'],
            ])>
                <div class="flex items-center justify-between">
                    <p @class([
                        'text-sm font-medium',
                        'text-primary-700 dark:text-primary-300' => $kpi['accent'],
                        'text-gray-500 dark:text-gray-400' => ! $kpi['accent'],
                    ])>{{ $kpi['label'] }}</p>
                    <span @class([
                        'flex h-8 w-8 items-center justify-center rounded-lg',
                        'bg-primary-100 text-primary-600 dark:bg-primary-500/20 dark:text-primary-300' => $kpi['accent'],
                        'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500' => ! $kpi['accent'],
                    ])>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icon'] }}" /></svg>
                    </span>
                </div>
                <p @class([
                    'mt-2 text-2xl font-bold tracking-tight',
                    'text-primary-700 dark:text-primary-200' => $kpi['accent'],
                    'text-gray-900 dark:text-white' => ! $kpi['accent'],
                ])>{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-5">
        {{-- Per vendor --}}
        <div class="lg:col-span-3 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-3.5 dark:border-white/5">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Per Vendor</h3>
                <span class="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ $this->rows->count() }} vendor</span>
            </div>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Vendor</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400">Trx</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400">Dibayar</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                    @forelse ($this->rows as $row)
                        <tr class="transition hover:bg-gray-50/70 dark:hover:bg-white/5">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $row['code'] }}</span>
                                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['order_count'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-white">{{ $rp($row['total_base_owed']) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-primary-600 dark:text-primary-400">{{ $rp($row['total_margin']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">Tidak ada transaksi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($this->rows->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 bg-gray-50 font-bold text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            <td class="px-5 py-3">TOTAL</td>
                            <td class="px-5 py-3 text-right">{{ number_format($totals['order_count'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right">{{ $rp($totals['total_base_owed']) }}</td>
                            <td class="px-5 py-3 text-right text-primary-700 dark:text-primary-300">{{ $rp($totals['total_margin']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Menu terlaris --}}
        <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-3.5 dark:border-white/5">
                <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Menu Terlaris</h3>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-white/5">
                @forelse ($this->topMenus as $i => $menu)
                    <div class="flex items-center justify-between px-5 py-2.5 text-sm transition hover:bg-gray-50/70 dark:hover:bg-white/5">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span @class([
                                'flex h-5 w-5 shrink-0 items-center justify-center rounded text-xs font-bold',
                                'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' => $i === 0,
                                'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-200' => $i === 1,
                                'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300' => $i === 2,
                                'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500' => $i > 2,
                            ])>{{ $i + 1 }}</span>
                            <span class="truncate font-medium text-gray-900 dark:text-white">{{ $menu->name }}</span>
                            <span class="shrink-0 text-xs text-gray-400">{{ $menu->vendor_code }}</span>
                        </div>
                        <span class="shrink-0 font-semibold text-gray-900 dark:text-white">{{ number_format($menu->qty, 0, ',', '.') }}x</span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-gray-400">Belum ada data.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
