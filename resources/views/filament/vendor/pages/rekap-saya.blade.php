<x-filament-panels::page>
    @php($totals = $this->totals)

    {{-- Filter periode --}}
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap gap-2">
            @foreach (['today' => 'Hari Ini', '7' => '7 Hari', '30' => '30 Hari', 'month' => 'Bulan Ini', 'lastmonth' => 'Bulan Lalu'] as $key => $label)
                <button wire:click="setPreset('{{ $key }}')" type="button"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-white/10 dark:text-gray-300">{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="w-full sm:w-48">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari</label>
                <input type="date" wire:model.live="from" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800" />
            </div>
            <div class="w-full sm:w-48">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai</label>
                <input type="date" wire:model.live="to" class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800" />
            </div>
            <p class="text-xs text-gray-400 sm:pb-2">{{ $this->dayCount }} hari</p>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totals['count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Porsi Terjual</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totals['qty'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-500/30 dark:bg-primary-500/10">
            <p class="text-sm font-medium text-primary-700 dark:text-primary-300">Jatah Saya (Diterima)</p>
            <p class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-300">{{ rupiah($totals['base_owed']) }}</p>
        </div>
    </div>

    {{-- Rincian per menu (terlaris di atas) --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-4 py-3 dark:border-white/5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Menu Terlaris</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Menu</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Qty</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Jatah Saya</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($this->items as $i => $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($item->qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-primary-600 dark:text-primary-400">{{ rupiah($item->base_owed) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-gray-400">Belum ada penjualan pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($this->items->isNotEmpty())
                <tfoot>
                    <tr class="border-t-2 border-gray-200 bg-gray-50 font-bold text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <td class="px-4 py-3" colspan="2">TOTAL</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals['qty'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-primary-700 dark:text-primary-300">{{ rupiah($totals['base_owed']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <p class="text-xs text-gray-400">Hanya transaksi lunas yang dihitung. Angka adalah jatah/pendapatan Anda.</p>
</x-filament-panels::page>
