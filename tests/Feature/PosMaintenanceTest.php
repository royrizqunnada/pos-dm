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

    public function test_login_pages_look_like_server_error(): void
    {
        foreach (['/', '/login', '/admin/login', '/vendor/login'] as $url) {
            $this->get($url)
                ->assertStatus(500)
                ->assertSee('Server Error');
        }
    }

    public function test_logged_in_users_are_blocked_too(): void
    {
        $this->actingAs($this->makeUser('cashier'))
            ->get('/kasir')
            ->assertStatus(500);

        $this->actingAs($this->makeUser('owner'))
            ->get('/admin')
            ->assertStatus(500);
    }

    public function test_app_is_open_when_flag_is_off(): void
    {
        config(['app.pos_maintenance' => false]);

        $this->get('/login')->assertOk();

        $this->actingAs($this->makeUser('cashier'))
            ->get('/kasir')
            ->assertOk();
    }
}
