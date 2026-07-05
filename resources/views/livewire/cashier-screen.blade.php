@php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
<div class="pos-root flex h-screen flex-col bg-slate-50"
    x-data="{ cartOpen: false, printMode: 'receipt', doPrint(mode) { this.printMode = mode; this.$nextTick(() => window.print()); } }"
    :class="printMode === 'kitchen' ? 'mode-kitchen' : 'mode-receipt'">
    <style>
        [x-cloak] { display: none !important; }
        /* Hilangkan margin halaman => buang header/footer browser (tanggal, URL, nomor halaman). */
        @page { margin: 0; }
        @media print {
            /* Paksa cetak warna latar (qty/badge) bila printer mendukung. */
            .print-layer, .print-layer * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
    <header class="flex items-center justify-between gap-2 border-b border-gray-200/80 bg-white px-3 py-3 shadow-sm sm:px-4 print:hidden">
        <div class="flex min-w-0 items-center gap-2.5">
            @if (file_exists(public_path('images/dm-kuliner-logo.png')))
                <img src="{{ asset('images/dm-kuliner-logo.png') }}" alt="DM Kuliner" class="h-10 w-auto shrink-0">
                <div class="min-w-0 border-l border-gray-200 pl-2.5">
                    <p class="truncate text-xs font-medium uppercase tracking-wide text-gray-400 leading-tight">Kasir</p>
                    <p class="truncate text-sm font-semibold text-gray-900 leading-tight">{{ optional(\App\Models\Location::find($locationId))->name ?? '-' }}</p>
                </div>
            @else
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#4A2410] to-[#7a4a2a] text-sm font-bold text-white shadow-sm">DM</div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 leading-tight">DM Kuliner POS</p>
                    <p class="truncate text-xs text-gray-500 leading-tight">{{ optional(\App\Models\Location::find($locationId))->name ?? 'Kasir' }}</p>
                </div>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
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
                                'inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition',
                                'bg-primary-600 text-white' => $activeVendorId === $vendor->id,
                                'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' => $activeVendorId !== $vendor->id,
                            ])>
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $this->vendorColor($vendor->id) }}"></span>
                            <span class="opacity-70">{{ $vendor->code }}</span> · {{ $vendor->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Grid menu (poll ringan agar menu sold-out hilang otomatis) --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 pb-24 md:pb-4" wire:poll.15s>
                @php($grouped = $this->menuItems->groupBy('vendor_id'))
                @forelse ($grouped as $vendorId => $items)
                    @php($vColor = $this->vendorColor($items->first()->vendor->id))
                    <div class="mb-6">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $vColor }}"></span>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">{{ $items->first()->vendor->code }}</span>
                            <h3 class="text-sm font-semibold text-gray-700">{{ $items->first()->vendor->name }}</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                            @foreach ($items as $item)
                                <button wire:click="addToCart({{ $item->id }})" wire:key="menu-{{ $item->id }}"
                                    x-data="{ pop: false }"
                                    @click="pop = true; clearTimeout($el._t); $el._t = setTimeout(() => pop = false, 600)"
                                    :class="pop ? 'border-primary-500 ring-2 ring-primary-200 scale-[0.97]' : 'border-gray-200'"
                                    class="group relative flex min-h-[88px] flex-col justify-between overflow-hidden rounded-2xl border bg-white p-3 pl-4 text-left transition duration-150 hover:border-primary-300 hover:shadow-md hover:shadow-gray-200/70 active:scale-[0.96]">
                                    <span class="absolute inset-y-0 left-0 w-1.5" style="background-color: {{ $vColor }}"></span>
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

        {{-- Backdrop keranjang (HP) --}}
        <div x-show="cartOpen" x-cloak @click="cartOpen = false" x-transition.opacity
            class="fixed inset-0 z-30 bg-gray-900/40 md:hidden print:hidden"></div>

        {{-- KANAN: keranjang (panel samping di tablet/desktop, panel geser di HP) --}}
        <aside class="fixed inset-y-0 right-0 z-40 flex w-[88%] max-w-sm flex-col border-l border-gray-200 bg-white shadow-2xl transition-transform duration-300 md:static md:z-auto md:w-full md:translate-x-0 md:shadow-none print:hidden"
            :class="cartOpen ? 'translate-x-0' : 'translate-x-full md:translate-x-0'">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-900">Keranjang
                    <span class="ml-1 text-sm font-normal text-gray-400">({{ $this->cartCount }})</span>
                </h2>
                <div class="flex items-center gap-3">
                    @if (! empty($cart))
                        <button wire:click="clearCart" class="text-sm font-medium text-red-500 hover:text-red-600">Kosongkan</button>
                    @endif
                    <button @click="cartOpen = false" class="text-gray-400 hover:text-gray-600 md:hidden">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
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
                <button wire:click="openPay" @click="cartOpen = false" @disabled(empty($cart))
                    class="w-full rounded-xl bg-primary-600 py-4 text-lg font-bold tracking-wide text-white shadow-lg shadow-primary-600/25 transition hover:bg-primary-700 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400 disabled:shadow-none">
                    BAYAR
                </button>
            </div>
        </aside>
    </div>

    {{-- Bar keranjang bawah (hanya HP) --}}
    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-gray-200 bg-white px-4 py-3 md:hidden print:hidden">
        <button @click="cartOpen = true" @disabled(empty($cart))
            class="flex w-full items-center justify-between rounded-xl bg-primary-600 px-4 py-3 text-white shadow-lg shadow-primary-600/25 transition active:scale-[0.99] disabled:bg-gray-200 disabled:text-gray-400 disabled:shadow-none">
            <span class="flex items-center gap-2 text-sm font-semibold">
                <span class="flex h-6 min-w-6 items-center justify-center rounded-full bg-white/25 px-1.5 text-xs font-bold">{{ $this->cartCount }}</span>
                Lihat Keranjang
            </span>
            <span class="text-base font-bold">{{ $rp($this->cartTotal) }}</span>
        </button>
    </div>

    {{-- ===== MODAL BAYAR ===== --}}
    @if ($showPayModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 p-4 print:hidden" wire:key="pay-modal">
            <div class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
                x-data="{
                    total: @js($this->cartTotal),
                    discount: @entangle('discountAmount'),
                    borneBy: @entangle('discountBorneBy'),
                    shipping: @entangle('shippingCost'),
                    cash: @entangle('cashReceived'),
                    method: @entangle('paymentMethod'),
                    get disc() { const d = parseInt(this.discount) || 0; return Math.max(0, Math.min(d, this.total)); },
                    get ship() { return Math.max(0, parseInt(this.shipping) || 0); },
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

                <div class="mb-4 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 p-4 ring-1 ring-slate-200/70">
                    <p class="text-center text-sm text-gray-500">Total Tagihan</p>
                    <p class="text-center text-4xl font-extrabold tracking-tight text-gray-900" x-text="rp(net)"></p>
                    {{-- Rincian tagihan (muncul bila ada diskon) --}}
                    <div class="mt-3 space-y-1 border-t border-dashed border-slate-300 pt-3 text-sm" x-show="disc > 0" x-cloak>
                        <div class="flex justify-between text-gray-500"><span>Subtotal</span><span x-text="rp(total)"></span></div>
                        <div class="flex justify-between text-red-500"><span>Diskon</span><span x-text="'− ' + rp(disc)"></span></div>
                    </div>
                    {{-- Ongkir hanya catatan (hak kurir) — tidak menambah total --}}
                    <div class="mt-2 flex items-center justify-between border-t border-dashed border-slate-300 pt-2 text-xs text-gray-500" x-show="ship > 0" x-cloak>
                        <span>Ongkir kurir <span class="text-gray-400">· catatan nota</span></span>
                        <span x-text="rp(ship)"></span>
                    </div>
                </div>

                {{-- Nomor meja & lantai --}}
                <div class="mb-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nomor Meja</label>
                        <input type="text" wire:model="tableNumber" inputmode="numeric" placeholder="mis. 12"
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-lg font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center justify-between text-sm font-medium text-gray-700">
                            <span>Lantai</span>
                            <span class="text-xs font-normal text-gray-400">opsional</span>
                        </label>
                        <input type="text" wire:model="floor" inputmode="numeric" placeholder="mis. 2"
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-lg font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    </div>
                </div>

                {{-- Diskon --}}
                <div class="mb-4">
                    <label class="mb-1 flex items-center justify-between text-sm font-medium text-gray-700">
                        <span>Diskon</span>
                        <span class="text-xs font-normal text-gray-400">opsional</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">Rp</span>
                        <input type="text" inputmode="numeric" placeholder="0"
                            :value="discount ? Number(discount).toLocaleString('id-ID') : ''"
                            @input="discount = parseInt($event.target.value.replace(/\D/g, '')) || null"
                            class="w-full rounded-xl border border-gray-200 py-2.5 pl-11 pr-4 text-right text-lg font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    </div>
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

                {{-- Ongkir (pesanan online) — hanya CATATAN di nota, hak kurir.
                     Tidak menambah total, kembalian, kas, atau pendapatan. --}}
                <div class="mb-4">
                    <label class="mb-1 flex items-center justify-between text-sm font-medium text-gray-700">
                        <span>Ongkir kurir <span class="font-normal text-gray-400">· pesanan online</span></span>
                        <span class="text-xs font-normal text-gray-400">opsional</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">Rp</span>
                        <input type="text" inputmode="numeric" placeholder="0"
                            :value="shipping ? Number(shipping).toLocaleString('id-ID') : ''"
                            @input="shipping = parseInt($event.target.value.replace(/\D/g, '')) || null"
                            class="w-full rounded-xl border border-gray-200 py-2.5 pl-11 pr-4 text-right text-lg font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2" x-show="ship === 0" x-cloak>
                        @foreach ([5000, 8000, 10000, 15000] as $preset)
                            <button type="button" @click="shipping = {{ $preset }}"
                                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50">+{{ number_format($preset / 1000, 0) }}rb</button>
                        @endforeach
                    </div>
                    <div class="mt-1.5 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Hanya tampil di nota — tidak menambah total.</p>
                        <button type="button" x-show="ship > 0" x-cloak @click="shipping = null"
                            class="text-xs font-medium text-gray-400 hover:text-red-500">Hapus</button>
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
                    <input type="text" inputmode="numeric" placeholder="0"
                        :value="cash ? Number(cash).toLocaleString('id-ID') : ''"
                        @input="cash = parseInt($event.target.value.replace(/\D/g, '')) || null"
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
                    class="w-full rounded-xl bg-green-600 py-4 text-lg font-bold text-white shadow-lg shadow-green-600/25 transition hover:bg-green-700 active:scale-[0.99]">
                    Konfirmasi Bayar
                </button>
            </div>
        </div>
    @endif

    {{-- ===== MODAL STRUK ===== --}}
    @if ($showReceipt && $this->lastOrder)
        @php($order = $this->lastOrder)
        @php($loc = $order->location)
        <div class="print-layer fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 p-4 print:static print:bg-white print:p-0">
            <div class="customer-receipt max-h-[92vh] w-full max-w-[20rem] overflow-y-auto rounded-2xl bg-white p-6 font-mono text-[13px] leading-tight text-gray-900 shadow-xl print:max-h-none print:max-w-none print:overflow-visible print:rounded-none print:p-2 print:shadow-none" id="receipt">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 print:hidden">
                    <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </div>

                {{-- Header --}}
                <div class="text-center">
                    <p class="text-base font-bold tracking-widest">{{ strtoupper($loc?->receipt_name ?: 'DM Kuliner') }}</p>
                    @if ($loc?->address)
                        <p class="text-[11px] text-gray-600">{{ $loc->address }}</p>
                    @endif
                    @if ($loc?->phone)
                        <p class="text-[11px] text-gray-600">{{ $loc->phone }}</p>
                    @endif
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
                    @if ($order->floor)
                        <div class="flex"><span class="w-16 shrink-0">Lantai</span><span>: <span class="font-bold">{{ $order->floor }}</span></span></div>
                    @endif
                    @if ($order->shipping_cost > 0)
                        <div class="flex"><span class="w-16 shrink-0">Tipe</span><span>: <span class="font-bold">Pesanan Online</span></span></div>
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
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between"><span>Diskon</span><span>-{{ number_format($order->discount_amount, 0, ',', '.') }}</span></div>
                    @endif
                </div>

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                <div class="flex justify-between text-base font-bold"><span>TOTAL</span><span>{{ number_format($order->total_amount, 0, ',', '.') }}</span></div>
                <div class="mt-1 flex justify-between"><span>{{ $order->payment_method === 'qris' ? 'QRIS' : 'Tunai' }}</span><span>{{ number_format($order->paid_amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Kembali</span><span>{{ number_format($order->change_amount, 0, ',', '.') }}</span></div>

                @if ($order->shipping_cost > 0)
                    {{-- Ongkir hanya catatan (hak kurir), dibayar langsung ke kurir. --}}
                    <div class="my-2 border-t border-dashed border-gray-400"></div>
                    <div class="flex justify-between"><span>Ongkir kurir</span><span>{{ number_format($order->shipping_cost, 0, ',', '.') }}</span></div>
                    <p class="text-[10px] text-gray-500">*ongkir dibayar langsung ke kurir</p>
                @endif

                <div class="my-2 border-t border-dashed border-gray-400"></div>

                {{-- Footer --}}
                <div class="text-center text-[11px] leading-snug">
                    <p>{{ $loc?->receipt_footer ?: 'Terima kasih & selamat menikmati!' }}</p>
                    @if ($loc?->instagram)
                        <p>IG: {{ $loc->instagram }}</p>
                    @endif
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
                    @php($vendor = $vendorItems->first()->vendor)
                    <div class="kitchen-ticket mx-auto max-w-sm bg-white p-5 font-mono text-gray-900">
                        {{-- Header terbingkai --}}
                        <div class="border-2 border-gray-900 px-3 py-2 text-center">
                            <p class="text-[11px] font-bold uppercase tracking-[0.25em]">Tiket Dapur</p>
                            <p class="text-2xl font-extrabold uppercase leading-tight">{{ optional($vendor)->name }}</p>
                            <p class="text-xs font-semibold">{{ optional($vendor)->code }}</p>
                        </div>

                        {{-- Order + meja --}}
                        <div class="mt-3 flex items-stretch justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-gray-500">No. Order</p>
                                <p class="truncate text-sm font-bold">{{ $order->order_number }}</p>
                                <p class="text-xs text-gray-600">{{ $order->created_at->format('d/m/Y · H:i') }}</p>
                            </div>
                            @if ($order->table_number)
                                <div class="flex flex-col items-center justify-center border-2 border-gray-900 px-3 py-1">
                                    <span class="text-[10px] font-bold uppercase leading-none">Meja{{ $order->floor ? ' · Lt '.$order->floor : '' }}</span>
                                    <span class="text-3xl font-extrabold leading-none">{{ $order->table_number }}</span>
                                </div>
                            @elseif ($order->floor)
                                <div class="flex flex-col items-center justify-center border-2 border-gray-900 px-3 py-1">
                                    <span class="text-[10px] font-bold uppercase leading-none">Lantai</span>
                                    <span class="text-3xl font-extrabold leading-none">{{ $order->floor }}</span>
                                </div>
                            @elseif ($order->shipping_cost > 0)
                                <div class="flex flex-col items-center justify-center border-2 border-gray-900 px-3 py-1.5">
                                    <span class="text-[10px] font-bold uppercase leading-none">Pesanan</span>
                                    <span class="text-lg font-extrabold uppercase leading-tight">Online</span>
                                </div>
                            @endif
                        </div>

                        <div class="my-3 border-t-2 border-dashed border-gray-400"></div>

                        {{-- Item besar & jelas --}}
                        <div class="space-y-2.5">
                            @foreach ($vendorItems as $item)
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-12 shrink-0 items-center justify-center rounded border-2 border-gray-900 text-lg font-extrabold">{{ $item->qty }}×</span>
                                    <span class="pt-1 text-lg font-bold uppercase leading-tight">{{ $item->name_snapshot }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="my-3 border-t-2 border-dashed border-gray-400"></div>

                        <p class="text-center text-xs font-semibold text-gray-600">Total {{ $vendorItems->sum('qty') }} porsi</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== MODAL BUKA SHIFT ===== --}}
    @if ($showOpenShift)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 p-4 print:hidden">
            <div class="max-h-[92vh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
                x-data="{ amount: @entangle('openingCash') }">
                <h3 class="mb-1 text-lg font-bold text-gray-900">Buka Shift</h3>
                <p class="mb-4 text-sm text-gray-500">Masukkan kas awal (modal laci) sebelum mulai melayani.</p>
                <label class="mb-1 block text-sm font-medium text-gray-700">Kas Awal</label>
                <input type="text" inputmode="numeric" placeholder="0"
                    :value="amount ? Number(amount).toLocaleString('id-ID') : ''"
                    @input="amount = parseInt($event.target.value.replace(/\D/g, '')) || null"
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
            <div class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
                x-data="{ counted: @entangle('countedCash').live }">
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
                    <input type="text" inputmode="numeric" placeholder="0"
                        :value="counted ? Number(counted).toLocaleString('id-ID') : ''"
                        @input="counted = parseInt($event.target.value.replace(/\D/g, '')) || null"
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
