<x-filament-widgets::widget>
    <div class="space-y-4">
        {{-- Sapaan --}}
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $greeting }},</p>
                <h2 class="truncate text-xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-2xl">Halo, {{ $name }} 👋</h2>
                <p class="mt-0.5 text-xs text-gray-400">{{ $date }}</p>
            </div>
            <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-600 text-base font-extrabold tracking-tight text-white shadow-lg shadow-primary-600/20 sm:flex">DM</span>
        </div>

        {{-- Aksi cepat --}}
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-3">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" @if ($action['navigate']) wire:navigate @endif
                    @class([
                        'group flex items-center gap-3 rounded-2xl border p-3 transition active:scale-[0.98]',
                        'border-primary-200 bg-primary-50 hover:border-primary-300 hover:bg-primary-100 dark:border-primary-500/30 dark:bg-primary-500/10 dark:hover:bg-primary-500/20' => $action['accent'],
                        'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:hover:bg-white/5' => ! $action['accent'],
                    ])>
                    <span @class([
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition group-hover:scale-105',
                        'bg-primary-600 text-white shadow-sm shadow-primary-600/30' => $action['accent'],
                        'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' => ! $action['accent'],
                    ])>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}" /></svg>
                    </span>
                    <span @class([
                        'truncate text-sm font-semibold',
                        'text-primary-700 dark:text-primary-300' => $action['accent'],
                        'text-gray-700 dark:text-gray-200' => ! $action['accent'],
                    ])>{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
