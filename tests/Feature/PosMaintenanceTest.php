<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.pos_maintenance' => true]);
    }

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

    public function test_cashier_is_blocked(): void
    {
        $this->actingAs($this->makeUser('cashier'))
            ->get('/kasir')
            ->assertStatus(503)
            ->assertSee('dinonaktifkan sementara');
    }

    public function test_owner_and_manager_still_have_access(): void
    {
        $this->actingAs($this->makeUser('owner'))
            ->get('/admin')
            ->assertOk();

        $this->actingAs($this->makeUser('manager'))
            ->get('/kasir')
            ->assertOk();
    }

    public function test_login_page_stays_open_for_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_app_is_open_when_flag_is_off(): void
    {
        config(['app.pos_maintenance' => false]);

        $this->actingAs($this->makeUser('cashier'))
            ->get('/kasir')
            ->assertOk();
    }
}
