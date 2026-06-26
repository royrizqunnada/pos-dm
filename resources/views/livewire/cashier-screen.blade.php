@php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
<div class="pos-root flex h-screen flex-col bg-slate-50"
    x-data="{ printMode: 'receipt', doPrint(mode) { this.printMode = mode; this.$nextTick(() => window.print()); } }"
    :class="printMode === 'kitchen' ? 'mode-kitchen' : 'mode-receipt'">
    <style>
        [x-cloak] { display: none !important; }
        @media print {
            /* Hanya cetak lapisan aktif (struk/tiket), sembunyikan UI lain. */
            .pos-root > :not(.print-layer) { display: none !important; }
            .print-layer { position: static !important; background: #fff !important; padding: 0 !important; }
            .mode-receipt .kitchen-tickets { display: none !important; }
            .mode-kitchen .customer-receipt { display: none !important; }
            .mode-kitchen .kitchen-tickets { display: block !important; }
            .kitchen-ticket { page-break-after: always; }
            .kitchen-ticket:last-child { page-break-after: auto; }
        }
    </style>
    {{-- Header --}}
    <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 print:hidden">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#7B1E22] text-sm font-bold text-white">DM</div>
            <div>
                <p class="text-sm font-semibold text-gray-900 leading-tight">DM Kuliner POS</p>
                <p class="text-xs text-gray-500 leading-tight">{{ optional(\App\Models\Location::find($locationId))->name ?? 'Kasir' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            {{-- Status shift --}}
            @if ($this->currentShift)
                <button wire:click="promptCloseShift" class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-100">
                    <span class="h-2 w-2 rounded-full bg-green-500"></span> Tutup Shift
                </button>
            @else
                <button wire:click="promptOpenShift" class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100">Buka Shift</button>
            @endif
            <span class="hidden text-sm text-gray-500 sm:inline">{{ auth()->user()->name }}</span>
            @if (auth()->user()->hasAnyRole(['owner', 'manager']))
                <a href="/admin" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Admin</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Keluar</button>
            </form>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        {{-- KIRI: daftar menu --}}
        <div class="flex flex-1 flex-col overflow-hidden">
            {{-- Search --}}
            <div class="border-b border-gray-200 bg-white px-4 py-3">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m1.7-4.05a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z"/></svg>
                    <input type="text" wire:model.live.debounce.250ms="search" placeholder="Cari menu..."
                        class="w-full rounded-xl border border-gray-200 bg-slate-50 py-3 pl-10 pr-4 text-base focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-100">
                </div>
                {{-- Tab vendor --}}
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                    <button wire:click="$set('activeVendorId', null)"
                        @class([
                            'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition',
                            'bg-primary-600 text-white' => $activeVendorId === null,
                            'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' => $activeVendorId !== null,
                        ])>Semua</button>
                    @foreach ($this->vendors as $vendor)
                        <button wire:click="$set('activeVendorId', {{ $vendor->id }})"
                            @class([
                                'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition',
                                'bg-primary-600 text-white' => $activeVendorId === $vendor->id,
                                'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' => $activeVendorId !== $vendor->id,
                            ])>
                            <span class="opacity-70">{{ $vendor->code }}</span> · {{ $vendor->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Grid menu (poll ringan agar menu sold-out hilang otomatis) --}}
            <div class="flex-1 overflow-y-auto px-4 py-4" wire:poll.15s>
                @php($grouped = $this->menuItems->groupBy('vendor_id'))
                @forelse ($grouped as $vendorId => $items)
                    <div class="mb-6">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">{{ $items->first()->vendor->code }}</span>
                            <h3 class="text-sm font-semibold text-gray-700">{{ $items->first()->vendor->name }}</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                            @foreach ($items as $item)
                                <button wire:click="addToCart({{ $item->id }})" wire:key="menu-{{ $item->id }}"
                                    x-data="{ pop: false }"
                                    @click="pop = true; clearTimeout($el._t); $el._t = setTimeout(() => pop = false, 600)"
                                    :class="pop ? 'border-primary-500 ring-2 ring-primary-200 scale-[0.97]' : 'border-gray-200'"
                                    class="group relative flex min-h-[88px] flex-col justify-between rounded-xl border bg-white p-3 text-left transition duration-150 hover:border-primary-300 hover:shadow-sm active:scale-[0.96]">
                                    <span x-show="pop" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 translate-y-1 scale-75"
                                        x-transition:enter-end="opacity-100 -translate-y-1 scale-100"
                                        x-transition:leave="transition ease-in duration-300"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0 -translate-y-4"
                                        class="pointer-events-none absolute -top-2 right-2 z-10 rounded-full bg-primary-600 px-2 py-0.5 text-xs font-bold text-white shadow-md">+1</span>
                                    <span class="line-clamp-2 text-sm font-medium text-gray-900">{{ $item->name }}</span>
                                    <span class="mt-2 text-base font-bold text-gray-900">{{ $rp($item->selling_price) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center text-gray-400">Menu tidak ditemukan.</div>
                @endforelse
            </div>
        </div>

        {{-- KANAN: keranjang --}}
        <aside class="flex w-full max-w-sm flex-col border-l border-gray-200 bg-white print:hidden">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-900">Keranjang
                    <span class="ml-1 text-sm font-normal text-gray-400">({{ $this->cartCount }})</span>
                </h2>
                @if (! empty($cart))
                    <button wire:click="clearCart" class="text-sm font-medium text-red-500 hover:text-red-600">Kosongkan</button>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-3">
                @forelse ($cart as $id => $line)
                    <div wire:key="cart-{{ $id }}" class="mb-3 rounded-xl border border-gray-100 bg-slate-50 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ $line['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $line['vendor_code'] }} · {{ $rp($line['selling_price']) }}</p>
                            </div>
                            <button wire:click="removeItem({{ $id }})" class="text-gray-300 hover:text-red-500">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button wire:click="decrement({{ $id }})" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-bold text-gray-700 active:scale-95">−</button>
                                <span class="w-8 text-center text-base font-semibold">{{ $line['qty'] }}</span>
                                <button wire:click="increment({{ $id }})" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-bold text-gray-700 active:scale-95">+</button>
                            </div>
                            <span class="text-sm font-bold text-gray-900">{{ $rp($line['selling_price'] * $line['qty']) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full flex-col items-center justify-center text-center text-gray-400">
                        <svg class="mb-2 h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                        <p class="text-sm">Keranjang kosong</p>
                        <p class="text-xs">Pilih menu di sebelah kiri</p>
                    </div>
                @endforelse
            </div>

            {{-- Total + bayar --}}
            <div class="border-t border-gray-200 px-4 py-4">
                <div class="mb-3 flex items-end justify-between">
                    <span class="text-sm text-gray-500">Total</span>
                    <span class="text-3xl font-extrabold text-gray-900">{{ $rp($this->cartTotal) }}</span>
                </div>
                <button wire:click="openPay" @disabled(empty($cart))
                    class="w-full rounded-xl bg-primary-600 py-4 text-lg font-bold text-white transition hover:bg-primary-700 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400">
                    BAYAR
                </button>
            </div>
        </aside>
    </div>

    {{-- ===== MODAL BAYAR ===== --}}
    @if ($showPayModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 p-4 print:hidden" wire:key="pay-modal">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                x-data="{
                    total: @js($this->cartTotal),
                    discount: @entangle('discountAmount'),
                    borneBy: @entangle('discountBorneBy'),
                    cash: @entangle('cashReceived'),
                    method: @entangle('paymentMethod'),
                    get disc() { const d = parseInt(this.discount) || 0; return Math.max(0, Math.min(d, this.total)); },
                    get net() { return Math.max(0, this.total - this.disc); },
                    get change() { return Math.max(0, (parseInt(this.cash) || 0) - this.net); },
                    rp(n) { return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID'); },
                }">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Pembayaran</h3>
                    <button wire:click="closePay" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mb-4 rounded-xl bg-slate-50 p-4 text-center">
                    <p class="text-sm text-gray-500">Total Tagihan</p>
                    <p x-show="disc > 0" x-cloak class="text-base font-medium text-gray-400 line-through" x-text="rp(total)"></p>
                    <p class="text-4xl font-extrabold text-gray-900" x-text="rp(net)"></p>
                    <p x-show="disc > 0" x-cloak class="mt-1 text-xs font-medium text-red-500" x-text="'Diskon ' + rp(disc)"></p>
                </div>

                {{-- Nomor meja --}}
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nomor Meja</label>
                    <input type="text" wire:model="tableNumber" inputmode="numeric" placeholder="mis. 12"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-lg font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                </div>

                {{-- Diskon --}}
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Diskon (opsional)</label>
                    <input type="number" x-model.number="discount" inputmode="numeric" placeholder="0" min="0"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-right text-lg font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    <div class="mt-2" x-show="disc > 0" x-cloak>
                        <p class="mb-1 text-xs text-gray-500">Ditanggung oleh</p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['owner' => 'Margin Saya', 'vendor' => 'Vendor', 'split' => 'Bagi Dua'] as $val => $label)
                                <button type="button" @click="borneBy = '{{ $val }}'"
                                    :class="borneBy === '{{ $val }}' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                    class="rounded-lg border py-2 text-xs font-semibold transition">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Metode --}}
                <div class="mb-4 grid grid-cols-2 gap-3">
                    <button type="button" @click="method = 'cash'"
                        :class="method === 'cash' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                        class="rounded-xl border py-3 text-base font-semibold transition">Tunai</button>
                    <button type="button" @click="method = 'qris'"
                        :class="method === 'qris' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                        class="rounded-xl border py-3 text-base font-semibold transition">QRIS</button>
                </div>

                <div class="mb-4" x-show="method === 'cash'">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Uang Diterima</label>
                    <input type="number" x-model.number="cash" inputmode="numeric" placeholder="0"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-right text-2xl font-bold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    @error('cashReceived') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    <div class="mt-2 grid grid-cols-4 gap-2">
                        <button type="button" @click="cash = net" class="rounded-lg border border-gray-200 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">Pas</button>
                        <button type="button" @click="cash = 20000" class="rounded-lg border border-gray-200 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">20rb</button>
                        <button type="button" @click="cash = 50000" class="rounded-lg border border-gray-200 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">50rb</button>
                        <button type="button" @click="cash = 100000" class="rounded-lg border border-gray-200 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">100rb</button>
                    </div>
                    <div class="mt-3 flex items-center justify-between rounded-xl bg-green-50 px-4 py-3">
                        <span class="text-sm font-medium text-green-700">Kembalian</span>
                        <span class="text-xl font-bold text-green-700" x-text="rp(change)"></span>
                    </div>
                </div>

                <div class="mb-4 rounded-xl border border-dashed border-gray-300 p-6 text-center text-gray-500" x-show="method === 'qris'" x-cloak>
                    <p class="text-sm">Tunjukkan QRIS ke pelanggan, lalu konfirmasi setelah pembayaran berhasil.</p>
                </div>

                <button wire:click="pay"
                    class="w-full rounded-xl bg-green-600 py-4 text-lg font-bold text-white transition hover:bg-green-700 active:scale-[0.99]">
                    Konfirmasi Bayar
                </button>
            </div>
        </div>
    @endif

    {{-- ===== MODAL STRUK ===== --}}
    @if ($showReceipt && $this->lastOrder)
        @php($order = $this->lastOrder)
        <div class="print-layer fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 p-4 print:static print:bg-white print:p-0">
            <div class="customer-receipt w-full max-w-[20rem] rounded-2xl bg-white p-6 font-mono text-[13px] leading-tight text-gray-900 shadow-xl print:max-w-none print:rounded-none print:p-2 print:shadow-none" id="receipt">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 print:hidden">
                    <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </div>

                {{-- Header --}}
                <div class="text-center">
                    <p class="text-base font-bold tracking-widest">DM KULINER</p>
                    <p class="text-[11px] text-gray-600">Jl. Lingkar Utara, Komplek Arjuna, Randudongkal</p>
                </div>

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                {{-- Info transaksi --}}
                <div class="space-y-0.5">
                    <div class="flex"><span class="w-16 shrink-0">No</span><span>: {{ $order->order_number }}</span></div>
                    <div class="flex"><span class="w-16 shrink-0">Tgl</span><span>: {{ $order->created_at->format('d/m/Y  H:i') }}</span></div>
                    <div class="flex"><span class="w-16 shrink-0">Kasir</span><span>: {{ optional($order->cashier)->name ?? '-' }}</span></div>
                    @if ($order->table_number)
                        <div class="flex"><span class="w-16 shrink-0">Meja</span><span>: <span class="font-bold">{{ $order->table_number }}</span></span></div>
                    @endif
                </div>

                @if ($order->status === 'void')
                    <p class="mt-2 text-center font-bold text-red-600">*** DIBATALKAN ***</p>
                @endif

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                {{-- Item dikelompokkan per vendor --}}
                @foreach ($order->items->groupBy(fn ($i) => optional($i->vendor)->name) as $vendorName => $vendorItems)
                    <p class="font-bold">[ {{ $vendorName ?: 'Lainnya' }} ]</p>
                    @foreach ($vendorItems as $item)
                        <p>{{ $item->name_snapshot }}</p>
                        <div class="flex justify-between">
                            <span class="pl-3 text-gray-600">{{ $item->qty }} x {{ number_format($item->selling_price_snapshot, 0, ',', '.') }}</span>
                            <span>{{ number_format($item->line_total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="h-2"></div>
                @endforeach

                <div class="border-t border-dashed border-gray-400"></div>

                <div class="mt-2 space-y-0.5">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($order->total_amount + $order->discount_amount, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span>Diskon</span><span>{{ number_format($order->discount_amount, 0, ',', '.') }}</span></div>
                </div>

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                <div class="flex justify-between text-base font-bold"><span>TOTAL</span><span>{{ number_format($order->total_amount, 0, ',', '.') }}</span></div>
                <div class="mt-1 flex justify-between"><span>{{ $order->payment_method === 'qris' ? 'QRIS' : 'Tunai' }}</span><span>{{ number_format($order->paid_amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Kembali</span><span>{{ number_format($order->change_amount, 0, ',', '.') }}</span></div>

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                {{-- Footer --}}
                <div class="text-center text-[11px] leading-snug">
                    <p>Terima kasih &amp; selamat menikmati!</p>
                    <p>IG: {{ '@dmkuliner.id' }}</p>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 print:hidden">
                    <button @click="doPrint('receipt')" class="rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cetak Struk</button>
                    <button @click="doPrint('kitchen')" class="rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Tiket Dapur</button>
                    <button wire:click="newOrder" class="col-span-2 rounded-xl bg-primary-600 py-3 text-sm font-semibold text-white hover:bg-primary-700">Transaksi Baru</button>
                    @if ($order->status !== 'void')
                        <button wire:click="voidLastOrder" wire:confirm="Batalkan transaksi ini? Tidak akan dihitung di settlement."
                            class="col-span-2 rounded-xl border border-red-200 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Batalkan Transaksi</button>
                    @endif
                </div>
            </div>

            {{-- Tiket dapur per vendor (hanya tampil saat cetak mode dapur) --}}
            <div class="kitchen-tickets hidden">
                @foreach ($order->items->groupBy('vendor_id') as $vendorItems)
                    <div class="kitchen-ticket mx-auto max-w-sm bg-white p-6">
                        <div class="text-center">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Tiket Dapur</p>
                            <h3 class="text-lg font-bold text-gray-900">{{ optional($vendorItems->first()->vendor)->name }}</h3>
                            <p class="text-xs text-gray-500">{{ optional($vendorItems->first()->vendor)->code }}</p>
                        </div>
                        <div class="my-3 border-t border-dashed border-gray-300"></div>
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900">{{ $order->order_number }}
                                <span class="font-normal text-gray-500">· {{ $order->created_at->format('H:i') }}</span>
                            </p>
                            @if ($order->table_number)
                                <span class="rounded-md bg-gray-900 px-2 py-0.5 text-sm font-bold text-white">Meja {{ $order->table_number }}</span>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @foreach ($vendorItems as $item)
                                <div class="flex items-center gap-3 text-base">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-gray-900 text-sm font-bold text-white">{{ $item->qty }}</span>
                                    <span class="font-medium text-gray-900">{{ $item->name_snapshot }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== MODAL BUKA SHIFT ===== --}}
    @if ($showOpenShift)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 p-4 print:hidden">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-1 text-lg font-bold text-gray-900">Buka Shift</h3>
                <p class="mb-4 text-sm text-gray-500">Masukkan kas awal (modal laci) sebelum mulai melayani.</p>
                <label class="mb-1 block text-sm font-medium text-gray-700">Kas Awal</label>
                <input type="number" wire:model="openingCash" inputmode="numeric" placeholder="0" min="0"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-right text-2xl font-bold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button wire:click="$set('showOpenShift', false)" class="rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button wire:click="openShift" class="rounded-xl bg-primary-600 py-3 text-sm font-bold text-white hover:bg-primary-700">Buka Shift</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== MODAL TUTUP SHIFT (rekonsiliasi) ===== --}}
    @if ($showCloseShift && $this->currentShift)
        @php($shift = $this->currentShift)
        @php($t = $shift->liveSalesTotals())
        @php($expected = $shift->opening_cash + $t['cash'])
        @php($variance = (int) $countedCash - $expected)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 p-4 print:hidden">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-1 text-lg font-bold text-gray-900">Tutup Shift &amp; Rekonsiliasi</h3>
                <p class="mb-4 text-sm text-gray-500">Dibuka {{ $shift->opened_at->format('d/m H:i') }} · {{ $t['count'] }} transaksi</p>

                <div class="space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Kas Awal</span><span>{{ $rp($shift->opening_cash) }}</span></div>
                    <div class="flex justify-between text-gray-600"><span>Penjualan Tunai</span><span>{{ $rp($t['cash']) }}</span></div>
                    <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-200 pt-2"><span>Kas Seharusnya</span><span>{{ $rp($expected) }}</span></div>
                    <div class="flex justify-between text-gray-600"><span>Penjualan QRIS</span><span>{{ $rp($t['qris']) }}</span></div>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Kas Fisik Dihitung</label>
                    <input type="number" wire:model.live="countedCash" inputmode="numeric" placeholder="0" min="0"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-right text-2xl font-bold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    @if ($countedCash !== null && $countedCash !== '')
                        <div @class([
                            'mt-3 flex items-center justify-between rounded-xl px-4 py-3',
                            'bg-green-50 text-green-700' => $variance === 0,
                            'bg-yellow-50 text-yellow-700' => $variance !== 0,
                        ])>
                            <span class="text-sm font-medium">Selisih</span>
                            <span class="text-xl font-bold">{{ $variance > 0 ? '+' : '' }}{{ $rp($variance) }}</span>
                        </div>
                        @if ($variance < 0)
                            <p class="mt-1 text-xs text-red-500">Kas kurang dari seharusnya.</p>
                        @elseif ($variance > 0)
                            <p class="mt-1 text-xs text-yellow-600">Kas lebih dari seharusnya.</p>
                        @endif
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button wire:click="$set('showCloseShift', false)" class="rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button wire:click="closeShift" class="rounded-xl bg-green-600 py-3 text-sm font-bold text-white hover:bg-green-700">Tutup Shift</button>
                </div>
            </div>
        </div>
    @endif
</div>
