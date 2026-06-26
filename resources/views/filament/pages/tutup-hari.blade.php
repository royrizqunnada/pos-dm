<x-filament-panels::page>
    {{-- Filter --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
        <div class="w-full sm:w-64">
            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
            <select wire:model.live="locationId"
                class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach ($this->getLocationOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-56">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
            <input type="date" wire:model.live="date"
                class="block w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500" />
        </div>
    </div>

    @php($totals = $this->totals)

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Total Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($totals['order_count'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Total Uang Masuk</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">Rp {{ number_format($totals['total_gross'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Dibayar ke Vendor</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">Rp {{ number_format($totals['total_base_owed'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-5">
            <p class="text-sm font-medium text-primary-700">Margin Saya (Hari Ini)</p>
            <p class="mt-1 text-2xl font-bold text-primary-700">Rp {{ number_format($totals['total_margin'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Tabel per vendor --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Vendor</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Transaksi</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Dibayar ke Vendor</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Margin Saya</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Total Kotor</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Rekening</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->rows as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $row['code'] }}</span>
                            <span class="ml-2 font-medium text-gray-900">{{ $row['name'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($row['order_count'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">Rp {{ number_format($row['total_base_owed'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-primary-600">Rp {{ number_format($row['total_margin'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($row['total_gross'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $row['payout_account'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                            Belum ada transaksi lunas pada tanggal ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($this->rows->isNotEmpty())
                <tfoot class="bg-gray-50 font-semibold text-gray-900">
                    <tr>
                        <td class="px-4 py-3">TOTAL</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals['order_count'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['total_base_owed'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-primary-700">Rp {{ number_format($totals['total_margin'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['total_gross'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-filament-panels::page>
