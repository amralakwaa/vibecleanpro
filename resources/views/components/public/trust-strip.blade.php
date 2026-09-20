@props(['businessProfile' => null, 'tone' => 'light'])

@php
    // One editable source (business profile → نقاط الثقة), rendered on the
    // homepage, About and every service page, so the same promise is never
    // retyped into page content and cannot drift between pages.
    $points = collect($businessProfile?->trust_points ?? [])->filter(fn ($point) => filled($point['title'] ?? null))->values();

    // The warranty page is linked only once it is actually published.
    $warrantyPage = \App\Models\Page::query()
        ->where('type', \App\Enums\PageType::Trust)
        ->where('slug', 'warranty')
        ->published()
        ->first();
    $warrantyUrl = $warrantyPage ? app(\App\Seo\UrlResolver::class)->urlForPage($warrantyPage) : null;
    $dark = $tone === 'dark';
@endphp

@if ($points->isNotEmpty())
    <section {{ $attributes->class([$dark ? 'bg-ink-950 text-white' : 'bg-white']) }} aria-label="التزاماتنا">
        <x-public.container width="wide" class="py-10 md:py-14">
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-{{ min(4, max(2, $points->count())) }}">
                @foreach ($points as $point)
                    <li @class([
                        'rounded-2xl p-5 ring-1',
                        'bg-white/5 ring-white/10' => $dark,
                        'bg-neutral-50 ring-ink-950/10' => ! $dark,
                    ])>
                        <p class="flex items-center gap-2 font-medium {{ $dark ? 'text-white' : 'text-ink-950' }}">
                            <x-public.icon :name="$point['icon'] ?? 'shield-check'" class="w-5 h-5 text-primary-600 shrink-0" />
                            {{ $point['title'] }}
                        </p>
                        @if (filled($point['description'] ?? null))
                            <p class="mt-2 text-sm leading-relaxed {{ $dark ? 'text-white/75' : 'text-neutral-600' }}">{{ $point['description'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($warrantyUrl)
                <a href="{{ $warrantyUrl }}" class="mt-5 inline-flex items-center gap-1.5 min-h-11 text-sm font-medium {{ $dark ? 'text-white hover:text-primary-200' : 'text-primary-700' }} underline-offset-4 hover:underline">
                    تفاصيل الضمان وشروط الخدمة
                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                </a>
            @endif
        </x-public.container>
    </section>
@endif
