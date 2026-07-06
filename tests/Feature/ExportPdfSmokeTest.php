<?php

namespace Tests\Feature;

use App\Filament\Pages\RekapPeriode;
use App\Filament\Pages\TutupHari;
use App\Models\{Location, MenuItem, Order, User, Vendor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportPdfSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedPaidSale(): User
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

        return $u;
    }

    /**
     * Jalankan aksi lewat pipeline Livewire (callAction) — bukan panggil method
     * langsung — supaya menangkap bug unduhan biner yang lolos JSON-encode Livewire
     * (error "Malformed UTF-8"). streamDownload harus dikenali sebagai file download.
     */
    public function test_rekap_periode_export_pdf_downloads(): void
    {
        Livewire::actingAs($this->seedPaidSale())
            ->test(RekapPeriode::class)
            ->callAction('exportPdf')
            ->assertFileDownloaded();
    }

    public function test_tutup_hari_export_pdf_downloads(): void
    {
        Livewire::actingAs($this->seedPaidSale())
            ->test(TutupHari::class)
            ->callAction('exportPdf')
            ->assertFileDownloaded();
    }

    public function test_rekap_periode_export_csv_downloads(): void
    {
        Livewire::actingAs($this->seedPaidSale())
            ->test(RekapPeriode::class)
            ->callAction('export')
            ->assertFileDownloaded();
    }
}
