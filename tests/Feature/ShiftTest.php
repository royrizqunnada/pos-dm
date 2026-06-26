<?php

namespace Tests\Feature;

use App\Livewire\CashierScreen;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private MenuItem $bakso; // jual 16000

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['owner', 'manager', 'cashier', 'vendor'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $this->location = Location::create(['name' => 'DM Test']);
        $vendor = Vendor::create(['location_id' => $this->location->id, 'code' => 'BB', 'name' => 'Bakso']);
        $this->bakso = MenuItem::create(['vendor_id' => $vendor->id, 'name' => 'Bakso', 'base_price' => 13500, 'margin' => 2500]);

        $this->cashier = User::create([
            'name' => 'Kasir', 'email' => 'kasir@test.dev',
            'password' => bcrypt('password'), 'location_id' => $this->location->id,
        ]);
        $this->cashier->assignRole('cashier');
    }

    public function test_open_pay_close_shift_reconciliation(): void
    {
        $component = Livewire::actingAs($this->cashier)->test(CashierScreen::class);

        // Buka shift dengan kas awal 100.000.
        $component->call('promptOpenShift')
            ->set('openingCash', 100000)
            ->call('openShift')
            ->assertSet('showOpenShift', false);

        $shift = Shift::where('status', 'open')->firstOrFail();
        $this->assertSame(100000, $shift->opening_cash);

        // Bayar 1 bakso tunai (16.000).
        $component->call('addToCart', $this->bakso->id)
            ->call('openPay')
            ->set('paymentMethod', 'cash')
            ->set('cashReceived', 20000)
            ->call('pay')
            ->call('newOrder');

        // Order terhubung ke shift.
        $this->assertSame($shift->id, Order::first()->shift_id);

        // Tutup shift, hitung kas fisik 116.000 (pas).
        $component->call('promptCloseShift')
            ->set('countedCash', 116000)
            ->call('closeShift')
            ->assertSet('showCloseShift', false);

        $shift->refresh();
        $this->assertSame('closed', $shift->status);
        $this->assertSame(16000, $shift->total_cash_sales);
        $this->assertSame(0, $shift->total_qris_sales);
        $this->assertSame(116000, $shift->expected_cash); // 100000 + 16000
        $this->assertSame(116000, $shift->counted_cash);
        $this->assertSame(0, $shift->cash_variance);
        $this->assertSame(1, $shift->order_count);
    }

    public function test_cash_variance_detects_shortage(): void
    {
        $component = Livewire::actingAs($this->cashier)->test(CashierScreen::class);

        $component->call('promptOpenShift')->set('openingCash', 50000)->call('openShift');

        $component->call('addToCart', $this->bakso->id)
            ->call('openPay')->set('paymentMethod', 'cash')->set('cashReceived', 16000)
            ->call('pay')->call('newOrder');

        // Kas seharusnya 66.000, fisik cuma 60.000 → kurang 6.000.
        $component->call('promptCloseShift')->set('countedCash', 60000)->call('closeShift');

        $shift = Shift::first();
        $this->assertSame(66000, $shift->expected_cash);
        $this->assertSame(-6000, $shift->cash_variance);
    }
}
