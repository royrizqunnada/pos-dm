<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Top Vendor Hari Ini</x-slot>

        @php($rows = $this->getRows())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-4 font-medium">Vendor</th>
                        <th class="py-2 pr-4 text-right font-medium">Item Terjual</th>
                        <th class="py-2 pr-4 text-right font-medium">Transaksi</th>
                        <th class="py-2 text-right font-medium">Jatah Vendor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="py-2 pr-4">
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $row->code }}</span>
                                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $row->name }}</span>
                            </td>
                            <td class="py-2 pr-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row->qty, 0, ',', '.') }}</td>
                            <td class="py-2 pr-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row->order_count, 0, ',', '.') }}</td>
                            <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">Rp {{ number_format($row->base_owed, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-400">Belum ada penjualan hari ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
