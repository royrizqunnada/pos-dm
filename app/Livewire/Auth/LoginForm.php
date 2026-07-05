<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LoginForm extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate();

        // Rate limit: maksimal 5 percobaan gagal per email+IP tiap 60 detik
        // (cegah brute-force / penebakan kata sandi).
        $key = 'login:'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, decaySeconds: 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        // Arahkan sesuai role:
        // - vendor          -> portal vendor (/vendor)
        // - owner           -> panel admin (/admin) langsung
        // - manager/kasir   -> layar kasir
        $user = Auth::user();

        $home = match (true) {
            $user->hasRole('vendor') => '/vendor',
            $user->hasRole('owner') => '/admin',
            default => route('kasir'),
        };

        return redirect()->intended($home);
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
