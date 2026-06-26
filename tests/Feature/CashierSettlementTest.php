<?php

namespace Tests\Feature;

use App\Livewire\CashierScreen;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierSettlementTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private Vendor $vendorA;

    private Vendor $vendorB;

    private MenuItem $bakso;

    private MenuItem $kopi;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['owner', 'manager', 'cashier', 'vendor'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->location = Location::create(['name' => 'DM Test']);

        $this->vendorA = Vendor::create([
            'location_id' => $this->location->id,
            'code' => 'BB',
            'name' => 'Bakso',
        ]);
        $this->vendorB = Vendor::create([
            'location_id' => $this->location->id,
            'code' => 'KK',
            'name' => 'Kopi',
        ]);

        // selling_price dihitung otomatis oleh model.
        $this->bakso = MenuItem::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Bakso Urat',
            'base_price' => 13500,
            'margin' => 2500,
        ]);
        $this->kopi = MenuItem::create([
            'vendor_id' => $this->vendorB->id,
            'name' => 'Kopi Susu',
            'base_price' => 14000,
            'margin' => 4000,
        ]);

        $this->cashier = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@test.dev',
            'password' => bcrypt('password'),
            'location_id' => $this->location->id,
        ]);
        $this->cashier->assignRole('cashier');
    }

    public function test_selling_price_is_auto_computed(): void
    {
        $this->assertSame(16000, $this->bakso->selling_price);
        $this->assertSame(18000, $this->kopi->selling_price);
    }

    public function test_cashier_creates_paid_multivendor_order_with_snapshots(): void
    {
        Livewire::actingAs($this->cashier)
            ->test(CashierScreen::class)
            ->call('addToCart', $this->bakso->id)
            ->call('increment', $this->bakso->id) // qty 2
            ->call('addToCart', $this->kopi->id)   // qty 1
            ->assertSet('cart.'.$this->bakso->id.'.qty', 2)
            ->call('openPay')
            ->set('paymentMethod', 'cash')
            ->set('cashReceived', 60000)
            ->call('pay')
            ->assertSet('showReceipt', true);

        $order = Order::with('items')->firstOrFail();

        $this->assertSame('paid', $order->status);
        $this->assertSame('cash', $order->payment_method);
        // 2*16000 + 1*18000 = 50000
        $this->assertSame(50000, $order->total_amount);
        $this->assertSame(60000, $order->paid_amount);
        $this->assertSame(10000, $order->change_amount);
        $this->assertCount(2, $order->items);

        $baksoItem = $order->items->firstWhere('menu_item_id', $this->bakso->id);
        $this->assertSame(13500, $baksoItem->base_price_snapshot);
        $this->assertSame(2500, $baksoItem->margin_snapshot);
        $this->assertSame(16000, $baksoItem->selling_price_snapshot);
        $this->assertSame(32000, $baksoItem->line_total);
    }

    public function test_cashier_screen_renders_for_authenticated_cashier(): void
    {
        $this->actingAs($this->cashier)
            ->get('/kasir')
            ->assertOk()
            ->assertSee('Bakso Urat')
            ->assertSee('Keranjang');
    }

    public function test_vendor_only_user_cannot_access_cashier_screen(): void
    {
        $vendorUser = User::create([
            'name' => 'Vendor User',
            'email' => 'vendor@test.dev',
            'password' => bcrypt('password'),
            'location_id' => $this->location->id,
        ]);
        $vendorUser->assignRole('vendor');

        $this->actingAs($vendorUser)->get('/kasir')->assertForbidden();
    }

    public function test_receipt_has_queue_number_and_thermal_layout(): void
    {
        Livewire::actingAs($this->cashier)
            ->test(CashierScreen::class)
            ->call('addToCart', $this->bakso->id)
            ->call('addToCart', $this->kopi->id)
            ->call('openPay')
            ->set('cashReceived', 50000)
            ->call('pay')
            ->assertSee('DM KULINER')
            ->assertSee('Antrian')
            ->assertSee('Terima kasih');

        $order = Order::firstOrFail();
        $this->assertSame(1, $order->queue_number);
        $this->assertStringStartsWith('DMK-', $order->order_number);
    }

    public function test_settlement_totals_per_vendor(): void
    {
        $this->createPaidOrder([
            [$this->bakso, 2],
            [$this->kopi, 1],
        ]);

        $rows = app(SettlementService::class)->forDate($this->location->id, now());
        $bb = $rows->firstWhere('code', 'BB');
        $kk = $rows->firstWhere('code', 'KK');

        $this->assertSame(27000, $bb['total_base_owed']); // 13500*2
        $this->assertSame(5000, $bb['total_margin']);     // 2500*2
        $this->assertSame(32000, $bb['total_gross']);     // 16000*2

        $this->assertSame(14000, $kk['total_base_owed']);
        $this->assertSame(4000, $kk['total_margin']);

        $totals = app(SettlementService::class)->totals($rows);
        $this->assertSame(9000, $totals['total_margin']); // margin saya
        $this->assertSame(50000, $totals['total_gross']);
    }

    public function test_price_change_does_not_affect_past_settlement(): void
    {
        $this->createPaidOrder([[$this->bakso, 1]]);

        // Harga menu naik besok.
        $this->bakso->update(['base_price' => 20000, 'margin' => 5000]);

        $rows = app(SettlementService::class)->forDate($this->location->id, now());
        $bb = $rows->firstWhere('code', 'BB');

        // Tetap pakai snapshot lama.
        $this->assertSame(13500, $bb['total_base_owed']);
        $this->assertSame(2500, $bb['total_margin']);
    }

    public function test_void_order_excluded_from_settlement(): void
    {
        $order = $this->createPaidOrder([[$this->bakso, 1]]);
        $order->update(['status' => 'void', 'voided_at' => now()]);

        $rows = app(SettlementService::class)->forDate($this->location->id, now());

        $this->assertTrue($rows->isEmpty());
    }

    /**
     * @param  array<int, array{0:MenuItem, 1:int}>  $lines
     */
    private function createPaidOrder(array $lines): Order
    {
        $order = Order::create([
            'location_id' => $this->location->id,
            'cashier_id' => $this->cashier->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 0,
            'paid_amount' => 0,
            'change_amount' => 0,
            'paid_at' => now(),
        ]);

        $total = 0;
        foreach ($lines as [$item, $qty]) {
            $order->items()->create([
                'menu_item_id' => $item->id,
                'vendor_id' => $item->vendor_id,
                'name_snapshot' => $item->name,
                'qty' => $qty,
                'base_price_snapshot' => $item->base_price,
                'margin_snapshot' => $item->margin,
                'selling_price_snapshot' => $item->selling_price,
                'line_total' => $item->selling_price * $qty,
            ]);
            $total += $item->selling_price * $qty;
        }

        $order->update(['total_amount' => $total, 'paid_amount' => $total]);

        return $order;
    }
}
