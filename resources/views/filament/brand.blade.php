@php($logo = public_path('images/dm-kuliner-logo.png'))
@if (file_exists($logo))
    <img src="{{ asset('images/dm-kuliner-logo.png') }}" alt="DM Kuliner — Café & Food Court" class="h-9 w-auto">
@else
    <div class="flex items-center gap-2.5">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#4A2410] text-sm font-extrabold tracking-tight text-white shadow-sm">DM</span>
        <span class="text-base font-bold text-gray-950 dark:text-white">DM Kuliner <span class="text-[#d97706] dark:text-[#e9971f]">POS</span></span>
    </div>
@endif
