<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        foreach (['owner', 'manager', 'cashier', 'vendor'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $location = Location::create(['name' => 'DM Test']);

        $user = User::create([
            'name' => ucfirst($role),
            'email' => $role.'@test.dev',
            'password' => bcrypt('password'),
            'location_id' => $location->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_owner_can_open_admin_dashboard(): void
    {
        $this->actingAs($this->makeUser('owner'))
            ->get('/admin')
            ->assertOk();
    }

    public function test_dashboard_summary_widget_renders(): void
    {
        \Livewire\Livewire::actingAs($this->makeUser('owner'))
            ->test(\App\Filament\Widgets\DashboardRingkasan::class)
            ->assertOk()
            ->assertSee('Ringkasan Hari Ini')
            ->assertSee('Top Vendor Hari Ini');
    }

    public function test_owner_can_open_settlement_page(): void
    {
        $this->actingAs($this->makeUser('owner'))
            ->get('/admin/tutup-hari')
            ->assertOk()
            ->assertSee('Margin Saya');
    }

    public function test_owner_can_open_master_data_pages(): void
    {
        $owner = $this->makeUser('owner');

        $this->actingAs($owner)->get('/admin/menu-items')->assertOk();
        $this->actingAs($owner)->get('/admin/vendors')->assertOk();
        $this->actingAs($owner)->get('/admin/orders')->assertOk();
    }

    public function test_cashier_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->makeUser('cashier'))
            ->get('/admin')
            ->assertForbidden();
    }
}
