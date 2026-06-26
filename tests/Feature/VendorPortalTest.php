<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\PenjualanSaya;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorPortalTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendorA;

    private Vendor $vendorB;

    private User $vendorUser;

    private MenuItem $itemA;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['owner', 'manager', 'cashier', 'vendor'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $location = Location::create(['name' => 'DM Test']);
        $this->vendorA = Vendor::create(['location_id' => $location->id, 'code' => 'AA', 'name' => 'Vendor A']);
        $this->vendorB = Vendor::create(['location_id' => $location->id, 'code' => 'BB', 'name' => 'Vendor B']);

        $this->itemA = MenuItem::create(['vendor_id' => $this->vendorA->id, 'name' => 'Menu A', 'base_price' => 10000, 'margin' => 2000]);
        MenuItem::create(['vendor_id' => $this->vendorB->id, 'name' => 'Menu B', 'base_price' => 9000, 'margin' => 1000]);

        $this->vendorUser = User::create([
            'name' => 'Vendor A User', 'email' => 'va@test.dev',
            'password' => bcrypt('password'), 'location_id' => $location->id,
            'vendor_id' => $this->vendorA->id,
        ]);
        $this->vendorUser->assignRole('vendor');
    }

    public function test_vendor_can_access_vendor_panel_but_not_admin(): void
    {
        $this->actingAs($this->vendorUser)->get('/vendor')->assertOk();
        $this->actingAs($this->vendorUser)->get('/admin')->assertForbidden();
    }

    public function test_vendor_menu_list_is_scoped_to_own_vendor(): void
    {
        $this->actingAs($this->vendorUser)
            ->get('/vendor/menu-items')
            ->assertOk()
            ->assertSee('Menu A')
            ->assertDontSee('Menu B');
    }

    public function test_penjualan_saya_shows_only_own_paid_sales(): void
    {
        // Order lunas berisi 2 Menu A.
        $order = Order::create([
            'location_id' => $this->vendorA->location_id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'paid', 'payment_method' => 'cash',
            'total_amount' => 24000, 'paid_amount' => 24000, 'change_amount' => 0,
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'menu_item_id' => $this->itemA->id, 'vendor_id' => $this->vendorA->id,
            'name_snapshot' => 'Menu A', 'qty' => 2,
            'base_price_snapshot' => 10000, 'margin_snapshot' => 2000,
            'selling_price_snapshot' => 12000, 'line_total' => 24000,
        ]);

        \Livewire\Livewire::actingAs($this->vendorUser)
            ->test(PenjualanSaya::class)
            ->assertSet('date', now()->toDateString())
            ->assertSee('Menu A')
            ->assertSee('20.000')  // jatah saya = 10.000 * 2
            ->assertSee('24.000'); // total terjual = 12.000 * 2
    }
}
