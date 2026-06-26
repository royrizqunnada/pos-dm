<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\Shift;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResetTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_clears_transactions_but_keeps_master_data(): void
    {
        Role::firstOrCreate(['name' => 'cashier']);
        $location = Location::create(['name' => 'DM Test']);
        $vendor = Vendor::create(['location_id' => $location->id, 'code' => 'BB', 'name' => 'Bakso']);
        $menu = MenuItem::create(['vendor_id' => $vendor->id, 'name' => 'Bakso', 'base_price' => 13500, 'margin' => 2500]);
        $cashier = User::create(['name' => 'Kasir', 'email' => 'k@test.dev', 'password' => bcrypt('x'), 'location_id' => $location->id]);
        $cashier->assignRole('cashier');

        $shift = Shift::create([
            'location_id' => $location->id, 'cashier_id' => $cashier->id,
            'opened_at' => now(), 'opening_cash' => 100000, 'status' => 'open',
        ]);
        $order = Order::create([
            'location_id' => $location->id, 'cashier_id' => $cashier->id, 'shift_id' => $shift->id,
            'order_number' => Order::generateOrderNumber(), 'status' => 'paid', 'payment_method' => 'cash',
            'total_amount' => 16000, 'paid_amount' => 16000, 'change_amount' => 0, 'paid_at' => now(),
        ]);
        $order->items()->create([
            'menu_item_id' => $menu->id, 'vendor_id' => $vendor->id, 'name_snapshot' => 'Bakso', 'qty' => 1,
            'base_price_snapshot' => 13500, 'margin_snapshot' => 2500, 'selling_price_snapshot' => 16000, 'line_total' => 16000,
        ]);
        Settlement::create([
            'location_id' => $location->id, 'vendor_id' => $vendor->id, 'date' => now()->toDateString(),
            'total_base_owed' => 13500, 'total_margin' => 2500, 'total_gross' => 16000,
        ]);

        $this->artisan('pos:reset-transactions --force')->assertSuccessful();

        // Transaksi terhapus.
        $this->assertSame(0, Order::count());
        $this->assertSame(0, \App\Models\OrderItem::count());
        $this->assertSame(0, Shift::count());
        $this->assertSame(0, Settlement::count());

        // Master data tetap.
        $this->assertSame(1, Location::count());
        $this->assertSame(1, Vendor::count());
        $this->assertSame(1, MenuItem::count());
        $this->assertSame(1, User::count());
    }
}
