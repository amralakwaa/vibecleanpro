<x-filament-panels::page>
    {{ $this->form }}

    {{-- Readiness: the real state of each integration, with where to fix
         it. States are computed, never inferred from "a value exists", and
         no secret value is ever printed here. --}}
    <x-filament::section heading="جاهزية التشغيل" description="حالة كل تكامل كما هي الآن، وسبب الحالة.">
        <div class="space-y-6">
            @foreach ($groups as $groupTitle => $items)
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $groupTitle }}</h3>
                    <ul class="mt-2 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($items as $item)
                            <li class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $item->title }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->detail }}</p>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <x-filament::badge :color="$item->color()">{{ $item->label }}</x-filament::badge>
                                    @if ($item->url && $item->actionLabel)
                                        <a href="{{ $item->url }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $item->actionLabel }}</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
