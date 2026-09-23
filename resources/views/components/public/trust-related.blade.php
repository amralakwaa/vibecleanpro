{{--
    "Related policies" at the foot of a policy page, so the visitor moves
    naturally through the Trust Center instead of relying on the footer. It
    shows the next three policies in the family's reading order (wrapping
    past the end), never the page you are already on. Navigation only.

    @param string $current  the current page slug
    @param array<string, array{icon: string, eyebrow: string, blurb: string, accent: string}> $policies
    @param \Illuminate\Support\Collection<string, string> $titles  slug => page title
    @param string|null $hubUrl
--}}
@props(['current', 'policies', 'titles', 'hubUrl' => null])

@php
    $slugs = array_keys($policies);
    $count = count($slugs);
    $start = array_search($current, $slugs, true);
    $ordered = $start === false
        ? $slugs
        : array_map(fn ($i) => $slugs[($start + 1 + $i) % $count], range(0, $count - 1));

    $related = collect($ordered)
        ->reject(fn ($slug) => $slug === $current)
        ->filter(fn ($slug) => isset($titles[$slug]))
        ->take(3)
        ->values();
@endphp

@if ($related->isNotEmpty())
    <section class="relative isolate bg-white border-t border-neutral-200 overflow-hidden">
        <x-public.container width="wide" class="py-14 md:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4 reveal">
                <div>
                    <p class="text-sm font-medium tracking-wide text-primary-700">سياسات ذات صلة</p>
                    <h2 class="mt-2 font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950 text-balance">استكمل رحلتك في مركز الثقة</h2>
                </div>
                @if ($hubUrl)
                    <a href="{{ $hubUrl }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:text-primary-800 transition-colors">
                        <x-public.icon name="shield-check" class="w-4 h-4" />
                        كل السياسات
                    </a>
                @endif
            </div>

            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 reveal">
                @foreach ($related as $slug)
                    @php($meta = $policies[$slug])
                    <li>
                        <x-public.trust-policy-card
                            :url="url('/'.$slug)"
                            :title="$titles[$slug]"
                            :icon="$meta['icon']"
                            :eyebrow="$meta['eyebrow']"
                            :blurb="$meta['blurb']"
                            :accent="$meta['accent']"
                        />
                    </li>
                @endforeach
            </ul>
        </x-public.container>
    </section>
@endif
