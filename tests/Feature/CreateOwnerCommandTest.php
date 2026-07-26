<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_akun_owner_baru(): void
    {
        $location = Location::create(['name' => 'DM Test']);

        $this->artisan('user:owner', [
            'email' => 'Owner2@dmkuliner.test',
            '--name' => 'Owner Kedua',
            '--password' => 'rahasia123',
        ])->assertSuccessful();

        $user = User::where('email', 'owner2@dmkuliner.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('Owner Kedua', $user->name);
        $this->assertSame($location->id, $user->location_id);
        $this->assertTrue($user->hasRole('owner'));
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_akun_lama_dipromosikan_jadi_owner_dengan_force(): void
    {
        Location::create(['name' => 'DM Test']);
        Role::firstOrCreate(['name' => 'cashier']);

        $user = User::create([
            'name' => 'Kasir Lama',
            'email' => 'lama@dmkuliner.test',
            'password' => bcrypt('password'),
        ]);
        $user->syncRoles(['cashier']);

        $this->artisan('user:owner', [
            'email' => 'lama@dmkuliner.test',
            '--name' => 'Owner Baru',
            '--password' => 'rahasia123',
            '--force' => true,
        ])->assertSuccessful();

        $user->refresh();

        $this->assertTrue($user->hasRole('owner'));
        $this->assertFalse($user->hasRole('cashier'));
        $this->assertSame(1, User::where('email', 'lama@dmkuliner.test')->count());
    }

    public function test_lokasi_tidak_dikenal_ditolak(): void
    {
        $this->artisan('user:owner', [
            'email' => 'owner3@dmkuliner.test',
            '--name' => 'Owner Tiga',
            '--password' => 'rahasia123',
            '--location' => 'Tidak Ada',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'owner3@dmkuliner.test']);
    }
}
