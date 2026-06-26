<?php

namespace Tests\Feature;

use App\Livewire\CashierScreen;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DiscountAllocator;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private MenuItem $bakso; // base 13500 / margin 2500 / jual 16000

    private MenuItem $kopi;  // base 14000 / margin 4000 / jual 18000

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['owner', 'manager', 'cashier', 'vendor'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $this->location = Location::create(['name' => 'DM Test']);
        $vendorA = Vendor::create(['location_id' => $this->location->id, 'code' => 'BB', 'name' => 'Bakso']);
        $vendorB = Vendor::create(['location_id' => $this->location->id, 'code' => 'KK', 'name' => 'Kopi']);

        $this->bakso = MenuItem::create(['vendor_id' => $vendorA->id, 'name' => 'Bakso', 'base_price' => 13500, 'margin' => 2500]);
        $this->kopi = MenuItem::create(['vendor_id' => $vendorB->id, 'name' => 'Kopi', 'base_price' => 14000, 'margin' => 4000]);

        $this->cashier = User::create([
            'name' => 'Kasir', 'email' => 'kasir@test.dev',
            'password' => bcrypt('password'), 'location_id' => $this->location->id,
        ]);
        $this->cashier->assignRole('cashier');
    }

    public function test_allocator_distributes_without_rounding_drift(): void
    {
        $lines = [
            1 => ['selling_total' => 16000, 'base_total' => 13500, 'margin_total' => 2500],
            2 => ['selling_total' => 18000, 'base_total' => 14000, 'margin_total' => 4000],
        ];

        $alloc = app(DiscountAllocator::class)->allocate($lines, 5000, DiscountAllocator::BORNE_OWNER);

        // Jumlah share persis = diskon.
        $this->assertSame(5000, $alloc[1]['share'] + $alloc[2]['share']);
        // Owner menanggung: potong dari margin dulu.
        $this->assertSame(0, $alloc[1]['from_base']);
        $this->assertSame(0, $alloc[2]['from_base']);
    }

    public function test_discount_borne_by_owner_reduces_only_margin(): void
    {
        // Diskon 4000 ditanggung owner. Vendor tetap dapat base penuh.
        $this->payViaCashier(discount: 4000, borneBy: 'owner');

        $rows = app(SettlementService::class)->forDate($this->location->id, now());
        $totals = app(SettlementService::class)->totals($rows);

        // base penuh: 13500 + 14000 = 27500 (tidak terpotong).
        $this->assertSame(27500, $totals['total_base_owed']);
        // margin penuh 6500 - diskon 4000 = 2500.
        $this->assertSame(2500, $totals['total_margin']);
        // gross 34000 - 4000 = 30000.
        $this->assertSame(30000, $totals['total_gross']);
    }

    public function test_discount_borne_by_vendor_reduces_only_base(): void
    {
        $this->payViaCashier(discount: 4000, borneBy: 'vendor');

        $totals = app(SettlementService::class)->totals(
            app(SettlementService::class)->forDate($this->location->id, now())
        );

        // base 27500 - 4000 = 23500.
        $this->assertSame(23500, $totals['total_base_owed']);
        // margin penuh tetap 6500.
        $this->assertSame(6500, $totals['total_margin']);
    }

    public function test_discount_split_reduces_both(): void
    {
        $this->payViaCashier(discount: 4000, borneBy: 'split');

        $totals = app(SettlementService::class)->totals(
            app(SettlementService::class)->forDate($this->location->id, now())
        );

        // Total potongan base + margin = 4000, gross berkurang 4000.
        $this->assertSame(30000, $totals['total_gross']);
        $this->assertSame(4000, (27500 - $totals['total_base_owed']) + (6500 - $totals['total_margin']));
        // Masing-masing terpotong (bagi dua).
        $this->assertGreaterThan(0, 27500 - $totals['total_base_owed']);
        $this->assertGreaterThan(0, 6500 - $totals['total_margin']);
    }

    private function payViaCashier(int $discount, string $borneBy): void
    {
        Livewire::actingAs($this->cashier)
            ->test(CashierScreen::class)
            ->call('addToCart', $this->bakso->id)
            ->call('addToCart', $this->kopi->id)
            ->call('openPay')
            ->set('discountAmount', $discount)
            ->set('discountBorneBy', $borneBy)
            ->set('paymentMethod', 'cash')
            ->set('cashReceived', 50000)
            ->call('pay')
            ->assertSet('showReceipt', true);

        $order = Order::firstOrFail();
        $this->assertSame($discount, $order->discount_amount);
        $this->assertSame(34000 - $discount, $order->total_amount);
    }
}
