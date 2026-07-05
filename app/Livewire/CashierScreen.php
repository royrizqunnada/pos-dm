<?php

namespace App\Livewire;

use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CashierScreen extends Component
{
    /**
     * Keranjang: menu_item_id => [id, name, vendor_id, vendor_code, base_price, margin, selling_price, qty]
     *
     * @var array<int, array<string, mixed>>
     */
    public array $cart = [];

    public string $search = '';

    public ?int $activeVendorId = null; // null = semua vendor

    public ?int $locationId = null;

    // Modal pembayaran
    public bool $showPayModal = false;

    public string $paymentMethod = 'cash';

    public ?int $cashReceived = null;

    public ?string $tableNumber = null;

    public ?string $floor = null;

    // Diskon
    public ?int $discountAmount = null;

    public string $discountBorneBy = 'owner'; // owner | vendor | split

    // Ongkir (pesanan online) — opsional, umumnya kosong.
    public ?int $shippingCost = null;

    // Struk
    public bool $showReceipt = false;

    public ?int $lastOrderId = null;

    // Shift
    public ?int $shiftId = null;

    public bool $showOpenShift = false;

    public ?int $openingCash = null;

    public bool $showCloseShift = false;

    public ?int $countedCash = null;

    public function mount(): void
    {
        // Hanya kasir, owner, dan manager yang boleh mengoperasikan layar kasir.
        abort_unless(
            auth()->user()?->hasAnyRole(['cashier', 'owner', 'manager']) ?? false,
            403
        );

        $this->locationId = auth()->user()?->location_id ?? Location::query()->value('id');

        // Muat shift yang sedang terbuka untuk kasir ini.
        $this->shiftId = \App\Models\Shift::query()
            ->where('cashier_id', auth()->id())
            ->where('location_id', $this->locationId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->value('id');
    }

    #[Computed]
    public function currentShift(): ?\App\Models\Shift
    {
        return $this->shiftId ? \App\Models\Shift::find($this->shiftId) : null;
    }

    public function promptOpenShift(): void
    {
        $this->openingCash = null;
        $this->showOpenShift = true;
    }

    public function openShift(): void
    {
        $shift = \App\Models\Shift::create([
            'location_id' => $this->locationId,
            'cashier_id' => auth()->id(),
            'opened_at' => now(),
            'opening_cash' => max(0, (int) $this->openingCash),
            'status' => 'open',
        ]);

        $this->shiftId = $shift->id;
        $this->showOpenShift = false;
    }

    public function promptCloseShift(): void
    {
        if (! $this->currentShift) {
            return;
        }

        $this->countedCash = null;
        $this->showCloseShift = true;
    }

    public function closeShift(): void
    {
        $shift = $this->currentShift;

        if (! $shift) {
            return;
        }

        $totals = $shift->liveSalesTotals();
        $expected = $shift->opening_cash + $totals['cash'];
        $counted = max(0, (int) $this->countedCash);

        $shift->update([
            'closed_at' => now(),
            'expected_cash' => $expected,
            'counted_cash' => $counted,
            'cash_variance' => $counted - $expected,
            'total_cash_sales' => $totals['cash'],
            'total_qris_sales' => $totals['qris'],
            'order_count' => $totals['count'],
            'status' => 'closed',
        ]);

        $this->shiftId = null;
        $this->showCloseShift = false;
        unset($this->currentShift);
    }

    /**
     * Warna aksen tetap per vendor (deterministik dari id).
     */
    public function vendorColor(int $vendorId): string
    {
        $palette = ['#2563eb', '#16a34a', '#db2777', '#ea580c', '#7c3aed', '#0891b2', '#ca8a04', '#dc2626', '#4f46e5', '#0d9488'];

        return $palette[$vendorId % count($palette)];
    }

    #[Computed]
    public function vendors(): Collection
    {
        return Vendor::query()
            ->where('location_id', $this->locationId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    #[Computed]
    public function menuItems(): Collection
    {
        return MenuItem::query()
            ->whereHas('vendor', fn ($q) => $q->where('location_id', $this->locationId))
            ->where('is_available', true)
            ->when($this->activeVendorId, fn ($q) => $q->where('vendor_id', $this->activeVendorId))
            ->when($this->search !== '', fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%'))
            ->with('vendor:id,code,name')
            ->orderBy('vendor_id')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function cartCount(): int
    {
        return array_sum(array_column($this->cart, 'qty'));
    }

    #[Computed]
    public function cartTotal(): int
    {
        $total = 0;
        foreach ($this->cart as $line) {
            $total += $line['selling_price'] * $line['qty'];
        }

        return $total;
    }

    #[Computed]
    public function discountValue(): int
    {
        // Diskon dibatasi maksimal sebesar total belanja.
        return max(0, min((int) $this->discountAmount, $this->cartTotal));
    }

    #[Computed]
    public function netTotal(): int
    {
        return max(0, $this->cartTotal - $this->discountValue);
    }

    /**
     * Ongkir hanya CATATAN di nota (100% hak kurir). Tidak menambah total,
     * tidak memengaruhi kembalian, kas, maupun pendapatan.
     */
    #[Computed]
    public function shippingValue(): int
    {
        return max(0, (int) $this->shippingCost);
    }

    #[Computed]
    public function changeAmount(): int
    {
        return max(0, (int) $this->cashReceived - $this->netTotal);
    }

    public function addToCart(int $menuItemId): void
    {
        if (isset($this->cart[$menuItemId])) {
            $this->cart[$menuItemId]['qty']++;

            return;
        }

        $item = MenuItem::with('vendor:id,code')->find($menuItemId);

        if (! $item || ! $item->is_available) {
            return;
        }

        $this->cart[$menuItemId] = [
            'id' => $item->id,
            'name' => $item->name,
            'vendor_id' => $item->vendor_id,
            'vendor_code' => $item->vendor->code,
            'base_price' => (int) $item->base_price,
            'margin' => (int) $item->margin,
            'selling_price' => (int) $item->selling_price,
            'qty' => 1,
        ];
    }

    public function increment(int $menuItemId): void
    {
        if (isset($this->cart[$menuItemId])) {
            $this->cart[$menuItemId]['qty']++;
        }
    }

    public function decrement(int $menuItemId): void
    {
        if (! isset($this->cart[$menuItemId])) {
            return;
        }

        if ($this->cart[$menuItemId]['qty'] <= 1) {
            unset($this->cart[$menuItemId]);

            return;
        }

        $this->cart[$menuItemId]['qty']--;
    }

    public function removeItem(int $menuItemId): void
    {
        unset($this->cart[$menuItemId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function openPay(): void
    {
        if (empty($this->cart)) {
            return;
        }

        $this->paymentMethod = 'cash';
        $this->cashReceived = null;
        $this->tableNumber = null;
        $this->floor = null;
        $this->discountAmount = null;
        $this->discountBorneBy = 'owner';
        $this->shippingCost = null;
        $this->showPayModal = true;
    }

    public function closePay(): void
    {
        $this->showPayModal = false;
    }

    public function setExact(): void
    {
        $this->cashReceived = $this->netTotal;
    }

    public function pay(): void
    {
        if (empty($this->cart)) {
            return;
        }

        $gross = $this->cartTotal;
        $discount = $this->discountValue;
        // Ongkir hanya catatan di nota (hak kurir) — tidak menambah tagihan.
        $shipping = $this->shippingValue;
        $net = $gross - $discount;

        if ($this->paymentMethod === 'cash') {
            $this->cashReceived = (int) $this->cashReceived;
            if ($this->cashReceived < $net) {
                throw ValidationException::withMessages([
                    'cashReceived' => 'Uang tunai kurang dari total.',
                ]);
            }
            $paid = $this->cashReceived;
        } else {
            // QRIS: dianggap dibayar pas.
            $paid = $net;
        }

        // Hitung alokasi diskon ke tiap baris (snapshot).
        $lineInputs = [];
        foreach ($this->cart as $id => $line) {
            $lineInputs[$id] = [
                'selling_total' => $line['selling_price'] * $line['qty'],
                'base_total' => $line['base_price'] * $line['qty'],
                'margin_total' => $line['margin'] * $line['qty'],
            ];
        }
        $allocations = app(\App\Services\DiscountAllocator::class)
            ->allocate($lineInputs, $discount, $this->discountBorneBy);

        $order = $this->persistPaidOrder($net, $discount, $shipping, $paid, $allocations);

        $this->lastOrderId = $order->id;
        $this->showPayModal = false;
        $this->showReceipt = true;
        $this->clearCart();
    }

    /**
     * Simpan order beserta itemnya dalam satu transaksi.
     * Nomor order dijaga unik: bila bentrok (mis. dua kasir bayar bersamaan),
     * regenerasi nomor lalu coba lagi maksimal 3x.
     *
     * @param  array<int|string, array{share:int, from_base:int, from_margin:int}>  $allocations
     */
    protected function persistPaidOrder(int $net, int $discount, int $shipping, int $paid, array $allocations): Order
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($net, $discount, $shipping, $paid, $allocations) {
                    $order = Order::create([
                        'location_id' => $this->locationId,
                        'cashier_id' => auth()->id(),
                        'shift_id' => $this->shiftId,
                        'order_number' => Order::generateOrderNumber(),
                        'table_number' => $this->tableNumber ? trim($this->tableNumber) : null,
                        'floor' => $this->floor ? trim($this->floor) : null,
                        'status' => 'paid',
                        'payment_method' => $this->paymentMethod,
                        // total_amount = tagihan toko (belanja - diskon). Ongkir TIDAK termasuk.
                        'total_amount' => $net,
                        'discount_amount' => $discount,
                        'discount_borne_by' => $discount > 0 ? $this->discountBorneBy : null,
                        // Ongkir hanya catatan di nota (hak kurir).
                        'shipping_cost' => $shipping,
                        'paid_amount' => $paid,
                        'change_amount' => max(0, $paid - $net),
                        'paid_at' => now(),
                    ]);

                    foreach ($this->cart as $id => $line) {
                        $alloc = $allocations[$id] ?? ['share' => 0, 'from_base' => 0, 'from_margin' => 0];

                        $order->items()->create([
                            'menu_item_id' => $line['id'],
                            'vendor_id' => $line['vendor_id'],
                            'name_snapshot' => $line['name'],
                            'qty' => $line['qty'],
                            // Snapshot harga WAJIB — settlement harus tetap akurat
                            // walau harga menu berubah nanti.
                            'base_price_snapshot' => $line['base_price'],
                            'margin_snapshot' => $line['margin'],
                            'selling_price_snapshot' => $line['selling_price'],
                            'line_total' => $line['selling_price'] * $line['qty'],
                            'discount_share' => $alloc['share'],
                            'discount_from_base' => $alloc['from_base'],
                            'discount_from_margin' => $alloc['from_margin'],
                        ]);
                    }

                    return $order;
                });
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Nomor order bentrok — coba lagi; menyerah setelah 3x percobaan.
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    #[Computed]
    public function lastOrder(): ?Order
    {
        if (! $this->lastOrderId) {
            return null;
        }

        return Order::with(['items.vendor:id,code,name', 'location', 'cashier:id,name'])->find($this->lastOrderId);
    }

    /**
     * Data ESC/POS (base64) untuk cetak otomatis via RawBT. Lebar 32 = 58mm.
     */
    #[Computed]
    public function receiptEscpos(): string
    {
        $order = $this->lastOrder;

        return $order ? base64_encode(\App\Support\ThermalReceipt::receipt($order, 32)) : '';
    }

    #[Computed]
    public function kitchenEscpos(): string
    {
        $order = $this->lastOrder;

        return $order ? base64_encode(\App\Support\ThermalReceipt::kitchenTickets($order, 32)) : '';
    }

    public function voidLastOrder(): void
    {
        $order = $this->lastOrder;

        if ($order && $order->status !== 'void') {
            $order->update(['status' => 'void', 'voided_at' => now()]);
            unset($this->lastOrder);
        }
    }

    public function newOrder(): void
    {
        $this->showReceipt = false;
        $this->lastOrderId = null;
        $this->cashReceived = null;
        $this->search = '';
        $this->activeVendorId = null;
    }

    public function render()
    {
        return view('livewire.cashier-screen');
    }
}
