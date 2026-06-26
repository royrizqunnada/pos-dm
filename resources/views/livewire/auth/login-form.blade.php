<div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-[#7B1E22] text-base font-bold text-white">DM</div>
            <h1 class="text-xl font-bold text-gray-900">DM Kuliner POS</h1>
            <p class="text-sm text-gray-500">Masuk untuk mulai melayani</p>
        </div>

        <form wire:submit="login" class="rounded-2xl border border-gray-200 bg-white p-6">
            <div class="mb-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input type="email" wire:model="email" autofocus
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi</label>
                <input type="password" wire:model="password"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-base focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                @error('password') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <label class="mb-4 flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Ingat saya
            </label>

            <button type="submit"
                class="w-full rounded-xl bg-primary-600 py-3 text-base font-bold text-white transition hover:bg-primary-700 active:scale-[0.99]">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memproses...</span>
            </button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-400">DM Kuliner — Randudongkal</p>
    </div>
</div>
