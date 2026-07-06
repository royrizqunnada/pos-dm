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
            ->assertSee('Top Vendor Hari Ini')
            ->assertSee('Bulan Ini');
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

    public function test_owner_can_open_users_and_period_recap(): void
    {
        $owner = $this->makeUser('owner');

        $this->actingAs($owner)->get('/admin/users')->assertOk();
        $this->actingAs($owner)->get('/admin/rekap-periode')->assertOk()->assertSee('Margin Saya');
    }

    public function test_manager_cannot_manage_users(): void
    {
        // Kelola pengguna khusus owner.
        $this->actingAs($this->makeUser('manager'))->get('/admin/users')->assertForbidden();
    }

    public function test_owner_creates_vendor_login_with_role_synced(): void
    {
        $owner = $this->makeUser('owner');
        $location = \App\Models\Location::first();
        $vendor = \App\Models\Vendor::create([
            'location_id' => $location->id, 'code' => 'XX', 'name' => 'X Vendor',
        ]);

        \Livewire\Livewire::actingAs($owner)
            ->test(\App\Filament\Resources\Users\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Vendor X',
                'email' => 'vx@test.dev',
                'role' => 'vendor',
                'vendor_id' => $vendor->id,
                'location_id' => $location->id,
                'password' => 'secret123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = \App\Models\User::where('email', 'vx@test.dev')->firstOrFail();
        $this->assertTrue($user->hasRole('vendor'));
        $this->assertSame($vendor->id, $user->vendor_id);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $user->password));
    }
}
