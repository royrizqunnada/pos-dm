@php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $st)
            <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md hover:shadow-gray-200/60 dark:border-white/10 dark:bg-gray-900 dark:hover:shadow-black/20">
                {{-- aksen tipis di sisi kiri --}}
                <span class="absolute inset-y-0 left-0 w-1" style="background-color: {{ $st['accent'] }}"></span>

                <p class="text-[11px] font-semibold uppercase tracking-[0.06em] text-gray-500 dark:text-gray-400">{{ $st['label'] }}</p>

                <p class="mt-2 text-[1.6rem] font-bold leading-none tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                    {{ $st['money'] ? $rp($st['value']) : number_format($st['value'], 0, ',', '.') }}@if (! empty($st['suffix']))<span class="text-sm font-medium text-gray-400"> {{ $st['suffix'] }}</span>@endif
                </p>

                <div class="mt-4 flex items-end justify-between gap-3">
                    <div class="min-w-0">
                        @if (! is_null($st['trend']))
                            <span @class([
                                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $st['trend'] >= 0,
                                'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' => $st['trend'] < 0,
                            ])>
                                <span aria-hidden="true">{{ $st['trend'] >= 0 ? '▲' : '▼' }}</span>{{ abs($st['trend']) }}%
                            </span>
                            <span class="ml-1 text-[11px] text-gray-400">vs kemarin</span>
                        @else
                            <span class="text-[11px] text-gray-400">Belum ada pembanding</span>
                        @endif
                        @if (! empty($st['sub']))
                            <p class="mt-1.5 truncate text-[11px] text-gray-400 dark:text-gray-500">{{ $st['sub'] }}</p>
                        @endif
                    </div>

                    @if ($st['spark'])
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none" class="h-8 w-24 shrink-0" aria-hidden="true">
                            <polyline points="{{ $st['spark'] }}" fill="none" stroke="{{ $st['accent'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" opacity="0.85" />
                        </svg>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
