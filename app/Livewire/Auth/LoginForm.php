<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
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

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        request()->session()->regenerate();

        // Kasir langsung ke layar kasir; owner/manager juga default ke kasir
        // (mereka bisa pindah ke /admin dari header).
        return redirect()->intended(route('kasir'));
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
