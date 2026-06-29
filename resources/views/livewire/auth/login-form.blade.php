<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-b from-slate-50 to-slate-100 px-4 py-10">
    {{-- Ornamen latar lembut --}}
    <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-[#7B1E22]/5 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-primary-500/5 blur-3xl"></div>

    <div class="relative w-full max-w-sm">
        <div class="mb-7 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#7B1E22] text-lg font-extrabold tracking-tight text-white shadow-lg shadow-[#7B1E22]/20">DM</div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">DM Kuliner POS</h1>
            <p class="mt-1 text-sm text-gray-500">Masuk untuk mulai melayani</p>
        </div>

        <form wire:submit="login" class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-xl shadow-slate-200/60 ring-1 ring-black/[0.02]">
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                <input type="email" wire:model="email" autofocus autocomplete="username"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                @error('email') <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Kata Sandi</label>
                <input type="password" wire:model="password" autocomplete="current-password"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                @error('password') <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <label class="mb-5 flex cursor-pointer items-center gap-2 text-sm text-gray-600 select-none">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Ingat saya
            </label>

            <button type="submit" wire:loading.attr="disabled" wire:target="login"
                class="w-full rounded-xl bg-primary-600 py-3 text-base font-bold text-white shadow-sm transition hover:bg-primary-700 active:scale-[0.99] disabled:opacity-70">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span class="inline-flex items-center justify-center gap-2" wire:loading wire:target="login">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    Memproses...
                </span>
            </button>
        </form>

        <p class="mt-5 text-center text-xs text-gray-400">DM Kuliner — Randudongkal</p>
    </div>
</div>
