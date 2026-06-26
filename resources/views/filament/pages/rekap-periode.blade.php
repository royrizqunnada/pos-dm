<x-filament-panels::page>
    @php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
    @php($totals = $this->totals)

    {{-- Filter --}}
    <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
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
        <div class="flex flex-wrap gap-2">
            @foreach (['today' => 'Hari ini', '7' => '7 hari', '30' => '30 hari', 'month' => 'Bulan ini', 'lastmonth' => 'Bulan lalu'] as $key => $label)
                <button wire:click="setPreset('{{ $key }}')" class="rounded-full border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5">{{ $label }}</button>
            @endforeach
            <span class="ml-auto self-center text-xs text-gray-400">{{ $this->dayCount }} hari</span>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totals['order_count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Penjualan</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $rp($totals['total_gross']) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Dibayar ke Vendor</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $rp($totals['total_base_owed']) }}</p>
        </div>
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-500/30 dark:bg-primary-500/10">
            <p class="text-sm font-medium text-primary-700 dark:text-primary-300">Margin Saya</p>
            <p class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-200">{{ $rp($totals['total_margin']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {{-- Per vendor --}}
        <div class="lg:col-span-3 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-white/5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Per Vendor</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300">Vendor</th>
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300">Trx</th>
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300">Dibayar</th>
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($this->rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $row['code'] }}</span>
                                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['order_count'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-900 dark:text-white">{{ $rp($row['total_base_owed']) }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-primary-600 dark:text-primary-400">{{ $rp($row['total_margin']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Tidak ada transaksi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($this->rows->isNotEmpty())
                    <tfoot class="bg-gray-50 font-semibold text-gray-900 dark:bg-white/5 dark:text-white">
                        <tr>
                            <td class="px-4 py-2.5">TOTAL</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($totals['order_count'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right">{{ $rp($totals['total_base_owed']) }}</td>
                            <td class="px-4 py-2.5 text-right text-primary-700 dark:text-primary-300">{{ $rp($totals['total_margin']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Menu terlaris --}}
        <div class="lg:col-span-2 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-white/5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Menu Terlaris</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($this->topMenus as $i => $menu)
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-gray-100 text-xs font-bold text-gray-500 dark:bg-white/10 dark:text-gray-300">{{ $i + 1 }}</span>
                            <span class="truncate font-medium text-gray-900 dark:text-white">{{ $menu->name }}</span>
                            <span class="shrink-0 text-xs text-gray-400">{{ $menu->vendor_code }}</span>
                        </div>
                        <span class="shrink-0 font-semibold text-gray-900 dark:text-white">{{ number_format($menu->qty, 0, ',', '.') }}x</span>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-gray-400">Belum ada data.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
