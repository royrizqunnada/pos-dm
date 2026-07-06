<?php

namespace Tests\Feature;

use App\Filament\Pages\RekapPeriode;
use App\Filament\Pages\TutupHari;
use App\Models\{Location, MenuItem, Order, User, Vendor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportPdfSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_pdf_and_csv_work(): void
    {
        foreach (['owner', 'manager', 'cashier', 'vendor'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }
        $loc = Location::create(['name' => 'DM']);
        $v = Vendor::create(['location_id' => $loc->id, 'code' => 'AA', 'name' => 'Vendor A']);
        $mi = MenuItem::create(['vendor_id' => $v->id, 'name' => 'Menu A', 'base_price' => 10000, 'margin' => 2000]);
        $o = Order::create(['location_id' => $loc->id, 'order_number' => Order::generateOrderNumber(), 'status' => 'paid', 'payment_method' => 'cash', 'total_amount' => 24000, 'paid_amount' => 24000, 'change_amount' => 0, 'paid_at' => now()]);
        $o->items()->create(['menu_item_id' => $mi->id, 'vendor_id' => $v->id, 'name_snapshot' => 'Menu A', 'qty' => 2, 'base_price_snapshot' => 10000, 'margin_snapshot' => 2000, 'selling_price_snapshot' => 12000, 'line_total' => 24000, 'discount_from_base' => 0, 'discount_from_margin' => 0, 'discount_share' => 0]);
        $u = User::create(['name' => 'O', 'email' => 'o@t.dev', 'password' => bcrypt('x'), 'location_id' => $loc->id]);
        $u->assignRole('owner');
        $this->actingAs($u);

        $p = new RekapPeriode();
        $p->mount();
        $r1 = $p->exportPdf();
        $this->assertSame(200, $r1->getStatusCode(), 'RekapPeriode PDF');

        $t = new TutupHari();
        $t->mount();
        $r2 = $t->exportPdf();
        $this->assertSame(200, $r2->getStatusCode(), 'TutupHari PDF');
    }
}
