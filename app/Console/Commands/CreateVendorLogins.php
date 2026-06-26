<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateVendorLogins extends Command
{
    protected $signature = 'vendor:logins
        {--password=vendor123 : Password default untuk akun yang baru dibuat}
        {--reset : Reset password vendor yang sudah ada ke password default}';

    protected $description = 'Buat 1 akun login untuk tiap vendor (portal read-only). Aman dijalankan ulang.';

    public function handle(): int
    {
        Role::firstOrCreate(['name' => 'vendor']);

        $defaultPassword = (string) $this->option('password');
        $rows = [];

        foreach (Vendor::with('location')->orderBy('code')->get() as $vendor) {
            $email = strtolower($vendor->code).'@dmkuliner.test';

            $user = User::where('email', $email)->first();
            $isNew = ! $user;

            if (! $user) {
                $user = new User([
                    'name' => $vendor->name,
                    'email' => $email,
                    'password' => Hash::make($defaultPassword),
                ]);
            } elseif ($this->option('reset')) {
                $user->password = Hash::make($defaultPassword);
            }

            // Selalu sinkronkan tautan vendor & lokasi.
            $user->name = $vendor->name;
            $user->vendor_id = $vendor->id;
            $user->location_id = $vendor->location_id;
            $user->save();
            $user->syncRoles(['vendor']);

            $rows[] = [
                $vendor->code,
                $vendor->name,
                $email,
                $isNew ? $defaultPassword : ($this->option('reset') ? $defaultPassword : '(tetap)'),
            ];
        }

        $this->info('Akun login vendor (portal: /vendor):');
        $this->table(['Kode', 'Vendor', 'Email', 'Password'], $rows);
        $this->warn('Sarankan tiap vendor ganti password setelah login pertama.');

        return self::SUCCESS;
    }
}
