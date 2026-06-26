<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache role/permission.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Role dasar.
        foreach (['owner', 'manager', 'cashier', 'vendor'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // 2. Import menu + buat lokasi & vendor dari CSV.
        Artisan::call('import:menu');
        $this->command->getOutput()->write(Artisan::output());

        $location = Location::query()->first();

        // 3. Akun demo.
        $owner = User::updateOrCreate(
            ['email' => 'owner@dmkuliner.test'],
            [
                'name' => 'Owner DM Kuliner',
                'password' => Hash::make('password'),
                'location_id' => $location?->id,
            ]
        );
        $owner->syncRoles(['owner']);

        $manager = User::updateOrCreate(
            ['email' => 'manager@dmkuliner.test'],
            [
                'name' => 'Manajer DM Kuliner',
                'password' => Hash::make('password'),
                'location_id' => $location?->id,
            ]
        );
        $manager->syncRoles(['manager']);

        $cashier = User::updateOrCreate(
            ['email' => 'kasir@dmkuliner.test'],
            [
                'name' => 'Kasir DM Kuliner',
                'password' => Hash::make('password'),
                'location_id' => $location?->id,
            ]
        );
        $cashier->syncRoles(['cashier']);

        // Satu akun login untuk tiap vendor (portal read-only).
        Artisan::call('vendor:logins');
        $this->command->getOutput()->write(Artisan::output());

        $this->command->info('Akun staf:');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['owner@dmkuliner.test', 'password', 'owner'],
                ['manager@dmkuliner.test', 'password', 'manager'],
                ['kasir@dmkuliner.test', 'password', 'cashier'],
            ]
        );
    }
}
