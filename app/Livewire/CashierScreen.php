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

    // Diskon
    public ?int $discountAmount = null;

    public string $discountBorneBy = 'owner'; // owner | vendor | split

    // Struk
    public bool $showReceipt = false;

    public ?int $lastOrderId = null;

    public function mount(): void
    {
        // Hanya kasir, owner, dan manager yang boleh mengoperasikan layar kasir.
        abort_unless(
            auth()->user()?->hasAnyRole(['cashier', 'owner', 'manager']) ?? false,
            403
        );

        $this->locationId = auth()->user()?->location_id ?? Location::query()->value('id');
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
        $this->discountAmount = null;
        $this->discountBorneBy = 'owner';
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

        $order = DB::transaction(function () use ($gross, $net, $discount, $paid, $allocations) {
            $order = Order::create([
                'location_id' => $this->locationId,
                'cashier_id' => auth()->id(),
                'order_number' => Order::generateOrderNumber(),
                'status' => 'paid',
                'payment_method' => $this->paymentMethod,
                'total_amount' => $net,
                'discount_amount' => $discount,
                'discount_borne_by' => $discount > 0 ? $this->discountBorneBy : null,
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

        $this->lastOrderId = $order->id;
        $this->showPayModal = false;
        $this->showReceipt = true;
        $this->clearCart();
    }

    #[Computed]
    public function lastOrder(): ?Order
    {
        if (! $this->lastOrderId) {
            return null;
        }

        return Order::with('items.vendor:id,code,name')->find($this->lastOrderId);
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
