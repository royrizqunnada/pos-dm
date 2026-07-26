<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateOwner extends Command
{
    protected $signature = 'user:owner
        {email? : Email untuk login}
        {--name= : Nama pemilik akun}
        {--password= : Kata sandi (kosong = digenerate acak)}
        {--location= : ID atau nama lokasi (kosong = lokasi pertama)}
        {--force : Lewati konfirmasi saat email sudah dipakai}';

    protected $description = 'Buat akun owner baru (akses penuh: kasir + admin). Aman dijalankan ulang.';

    public function handle(): int
    {
        Role::firstOrCreate(['name' => 'owner']);

        $email = strtolower(trim((string) ($this->argument('email') ?: $this->ask('Email untuk login'))));
        $name = trim((string) ($this->option('name') ?: $this->ask('Nama pemilik akun', 'Owner DM Kuliner')));

        $validator = Validator::make(
            ['email' => $email, 'name' => $name],
            ['email' => ['required', 'email', 'max:255'], 'name' => ['required', 'string', 'max:255']]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $location = $this->resolveLocation();

        if ($this->option('location') && ! $location) {
            $this->error('Lokasi "'.$this->option('location').'" tidak ditemukan.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        // Password: pakai opsi, atau generate acak supaya tidak ada default yang mudah ditebak.
        $password = (string) ($this->option('password') ?? '');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(12, symbols: false);
        }

        if ($user) {
            $this->warn('Email '.$email.' sudah dipakai akun "'.$user->name.'" (peran: '.($user->getRoleNames()->implode(', ') ?: '-').').');

            if (! $this->option('force') && ! $this->confirm('Jadikan akun ini owner dan setel ulang kata sandinya?')) {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        } else {
            $user = new User(['email' => $email]);
        }

        $user->name = $name;
        $user->password = Hash::make($password);
        $user->vendor_id = null; // owner tidak terikat vendor manapun.
        $user->location_id = $location?->id ?? $user->location_id;
        $user->save();
        $user->syncRoles(['owner']);

        $this->newLine();
        $this->info('Akun owner siap dipakai:');
        $this->table(
            ['Nama', 'Email', 'Kata Sandi', 'Lokasi'],
            [[
                $user->name,
                $user->email,
                $generated ? $password : '(sesuai --password)',
                $user->location?->name ?? '-',
            ]]
        );

        $this->line('Login admin: '.rtrim((string) config('app.url'), '/').'/admin');
        $this->warn('Simpan kata sandi ini sekarang — tidak bisa ditampilkan lagi. Ganti setelah login pertama.');

        return self::SUCCESS;
    }

    private function resolveLocation(): ?Location
    {
        $option = $this->option('location');

        if (blank($option)) {
            return Location::query()->orderBy('id')->first();
        }

        return Location::query()
            ->when(
                is_numeric($option),
                fn ($query) => $query->whereKey((int) $option),
                fn ($query) => $query->whereRaw('LOWER(name) = ?', [strtolower((string) $option)])
            )
            ->first();
    }
}
