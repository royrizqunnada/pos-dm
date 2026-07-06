<x-filament-panels::page>
    @php($totals = $this->totals)

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Vendor</p>
            <p class="text-lg font-bold text-gray-900">{{ optional($this->vendor)->name ?? '—' }}</p>
        </div>
        <div class="text-left sm:text-right">
            <p class="text-sm text-gray-500">Hari Ini</p>
            <p class="text-base font-semibold text-gray-900">{{ $this->tanggalLabel }}</p>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($totals['count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-5">
            <p class="text-sm font-medium text-primary-700">Jatah Saya (Diterima)</p>
            <p class="mt-1 text-2xl font-bold text-primary-700">Rp {{ number_format($totals['base_owed'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Rincian per menu --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Menu</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Qty</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Jatah Saya</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-primary-600">Rp {{ number_format($item->base_owed, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-10 text-center text-gray-400">Belum ada penjualan hari ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400">Catatan: data hanya menampilkan transaksi lunas. Pesanan yang dibatalkan tidak dihitung.</p>
</x-filament-panels::page>
