{{--
    Contents navigation for a long policy page, built from the page's own
    <h2> headings (see TrustSections). Desktop: a quiet sticky rail beside
    the text. Mobile: a compact <details> that collapses so it never pushes
    the reading start down the screen. Anchor links only - no JS - and the
    numbering matches the section numbers rendered in the prose.

    @param array<int, array{id: string, label: string}> $sections
--}}
@props(['sections' => []])

@if (count($sections) > 1)
    {{-- Mobile: collapsible contents --}}
    <details class="lg:hidden group rounded-2xl bg-white ring-1 ring-ink-950/10 shadow-sm overflow-hidden">
        <summary class="flex items-center justify-between gap-3 px-5 py-4 cursor-pointer list-none font-medium text-ink-950">
            <span class="inline-flex items-center gap-2">
                <x-public.icon name="clipboard" class="w-5 h-5 text-primary-600" />
                محتويات الصفحة
            </span>
            <x-public.icon name="chevron-down" class="w-4 h-4 text-neutral-400 transition-transform group-open:rotate-180" />
        </summary>
        <ol class="px-5 pb-4 pt-1 space-y-0.5 border-t border-neutral-100">
            @foreach ($sections as $section)
                <li>
                    <a href="#{{ $section['id'] }}" class="flex items-baseline gap-2.5 py-2 text-sm text-neutral-600 hover:text-primary-700 transition-colors">
                        <span class="font-display text-xs text-primary-500 tabular-nums shrink-0">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="leading-snug">{{ $section['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </details>

    {{-- Desktop: sticky rail --}}
    <nav aria-label="محتويات الصفحة" class="hidden lg:block lg:sticky lg:top-24 self-start">
        <p class="text-xs font-medium tracking-wide uppercase text-neutral-400">في هذه الصفحة</p>
        <ol class="mt-4 space-y-0.5 border-s border-neutral-200 ps-4">
            @foreach ($sections as $section)
                <li>
                    <a href="#{{ $section['id'] }}" class="group flex items-baseline gap-2.5 py-1.5 text-sm text-neutral-600 hover:text-primary-700 transition-colors">
                        <span class="font-display text-xs text-primary-500 tabular-nums shrink-0">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="leading-snug">{{ $section['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>
@endif
