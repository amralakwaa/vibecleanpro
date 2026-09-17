{{--
    Renders a Page's content_blocks (see app/Filament/Support/ContentBlocks.php
    for the authored shape of each block type) using the design system
    components. This is the ONLY place that interprets block data - page
    templates never hand-render a block's markup themselves.

    'faq' and 'related_content' blocks are placement markers only (no data
    of their own - see ContentBlocks.php's docblock), so the real data for
    them is passed in here rather than re-derived.

    A page that needs one block type somewhere other than its place in the
    editorial flow does NOT hand-render it. It calls this component twice
    and filters by TYPE, which keeps all block-data interpretation here:

        <x-public.blocks :blocks="$blocks" :except="['faq']" ... />
        ...other page sections...
        <x-public.blocks :blocks="$blocks" :only="['faq']" ... />

    `only` and `except` accept a block type or an array of them. Relative
    order within each pass is untouched, and because the two passes are
    disjoint no block can render twice.

    @param \Illuminate\Support\Collection<int, \App\Models\ContentBlock> $blocks
    @param \Illuminate\Support\Collection<int, \App\Models\Faq>|null $faqs
    @param \Illuminate\Support\Collection|null $related
    @param string|null $relatedItemType service|area
    @param string|array|null $only render ONLY these block types
    @param string|array|null $except render everything but these types
    @param string $width container width for the READING blocks
           (rich_text, image, gallery, faq, cta). Pass "narrow" on a page
           whose job is reading, so long prose sits in a comfortable
           Arabic measure instead of spanning the full container; the
           grid-shaped blocks (features, steps, packages, related_content)
           keep the default width regardless, because a four-column grid
           has no business inside a reading column.
--}}
@props([
    'blocks',
    'faqs' => null,
    'related' => null,
    'relatedItemType' => null,
    'only' => null,
    'except' => null,
    'width' => 'default',
])

@php
    $visibleBlocks = $blocks->where('is_active', true)
        ->when($only !== null, fn ($blocks) => $blocks->whereIn('type', (array) $only))
        ->when($except !== null, fn ($blocks) => $blocks->whereNotIn('type', (array) $except));

    // Media is preloaded for the blocks this pass will actually render,
    // so a filtered pass never fetches images for blocks it skips - and a
    // pass whose blocks reference none skips the query entirely.
    $mediaIds = collect();
    foreach ($visibleBlocks as $block) {
        $mediaIds->push($block->data['media_id'] ?? null);
        $mediaIds->push($block->data['background_media_id'] ?? null);
        $mediaIds = $mediaIds->merge($block->data['media_ids'] ?? []);
    }
    $mediaIds = $mediaIds->filter()->unique();
    $media = $mediaIds->isEmpty()
        ? collect()
        : \App\Models\Media::query()->whereIn('id', $mediaIds)->get()->keyBy('id');
@endphp

@foreach ($visibleBlocks as $block)
    @switch($block->type)
        @case('hero')
            {{-- An editor-placed moment band (standalone/landing pages):
                 navy field, optional real photograph behind a scrim, one
                 heading, one optional action. --}}
            @php($heroMedia = $media->get($block->data['background_media_id'] ?? null))
            @if (! empty($block->data['heading']))
                <section class="relative isolate overflow-hidden bg-ink-950 text-white">
                    @if ($heroMedia)
                        <img src="{{ $heroMedia->url() }}" alt="{{ $heroMedia->alt_text ?? '' }}" loading="lazy" width="1600" height="900"
                            class="absolute inset-0 -z-10 w-full h-full object-cover opacity-40">
                        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink-950 via-ink-950/70 to-ink-950/30" aria-hidden="true"></div>
                    @endif
                    <x-public.container width="wide" class="py-16 md:py-24">
                        <div class="max-w-2xl">
                            <h2 class="font-display text-2xl md:text-4xl font-medium tracking-tight text-white text-balance">{{ $block->data['heading'] }}</h2>
                            @if (! empty($block->data['subheading']))
                                <p class="mt-4 text-lg text-ink-200 leading-relaxed">{{ $block->data['subheading'] }}</p>
                            @endif
                            @if (! empty($block->data['cta_label']) && ! empty($block->data['cta_url']))
                                <div class="mt-8">
                                    <x-public.button :href="$block->data['cta_url']" variant="cta" size="lg" icon="check-circle">{{ $block->data['cta_label'] }}</x-public.button>
                                </div>
                            @endif
                        </div>
                    </x-public.container>
                </section>
            @endif
            @break

        @case('inclusions')
            {{-- "What is included / what is not" as a split: the included
                 list on a tinted panel with check cues, the excluded list
                 on a white panel with minus cues - two surfaces, read at a
                 glance, no card grid. Either list may be empty; the block
                 renders only when at least one item exists. --}}
            @php($included = array_values(array_filter(array_map(fn ($item) => trim((string) ($item['item'] ?? '')), $block->data['included'] ?? []))))
            @php($excluded = array_values(array_filter(array_map(fn ($item) => trim((string) ($item['item'] ?? '')), $block->data['excluded'] ?? []))))
            @if ($included !== [] || $excluded !== [])
                <x-public.section tone="surface">
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 mb-8 md:mb-10 text-balance reveal">{{ $block->data['heading'] }}</h2>
                    @endif
                    <div @class(['grid gap-5 md:gap-6 reveal', 'md:grid-cols-2' => $included !== [] && $excluded !== []])>
                        @if ($included !== [])
                            <div class="surface-tint relative overflow-hidden rounded-3xl p-6 md:p-8 ring-1 ring-primary-200/60">
                                <div class="glow-primary absolute -top-16 -end-16 w-48 h-48 opacity-60" aria-hidden="true"></div>
                                <h3 class="relative flex items-center gap-2.5 font-display text-lg font-medium text-ink-950">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-600 text-white" aria-hidden="true"><x-public.icon name="check" class="w-4 h-4" /></span>
                                    ما تشمله الخدمة
                                </h3>
                                <ul class="relative mt-5 space-y-2.5">
                                    @foreach ($included as $item)
                                        <li class="flex gap-3 text-ink-950">
                                            <x-public.icon name="check" class="w-4 h-4 mt-1.5 text-primary-600 shrink-0" />
                                            <span class="leading-relaxed">{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($excluded !== [])
                            <div class="rounded-3xl bg-white p-6 md:p-8 ring-1 ring-ink-950/10">
                                <h3 class="flex items-center gap-2.5 font-display text-lg font-medium text-ink-950">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-neutral-200 text-neutral-700" aria-hidden="true"><x-public.icon name="minus" class="w-4 h-4" /></span>
                                    ما لا تشمله
                                </h3>
                                <ul class="mt-5 space-y-2.5">
                                    @foreach ($excluded as $item)
                                        <li class="flex gap-3 text-neutral-600">
                                            <x-public.icon name="minus" class="w-4 h-4 mt-1.5 text-neutral-400 shrink-0" />
                                            <span class="leading-relaxed">{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </x-public.section>
            @endif
            @break

        @case('price_factors')
            {{-- Why the final price differs: the honest companion to a
                 "starting from" price, laid out to be scanned in seconds -
                 one white tile per factor with a large numeral, on the
                 tinted field. Every factor is editor-written; nothing is
                 invented here. --}}
            @php($factors = array_values(array_filter($block->data['items'] ?? [], fn ($item) => filled($item['title'] ?? null))))
            @if ($factors !== [])
                <x-public.section tone="tint">
                    <div class="max-w-2xl reveal">
                        <p class="text-sm font-medium tracking-wide text-primary-700">قبل أن تطلب</p>
                        <h2 class="mt-2 font-display text-2xl md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance">{{ $block->data['heading'] ?? 'ما الذي يحدد السعر؟' }}</h2>
                    </div>
                    {{-- Four factors sit as one row of four, not three plus an orphan. --}}
                    <ol @class(['mt-8 md:mt-10 grid gap-4 md:gap-5 sm:grid-cols-2 reveal', 'lg:grid-cols-3' => count($factors) === 3 || count($factors) >= 5, 'lg:grid-cols-4' => count($factors) === 4])>
                        @foreach ($factors as $factor)
                            <li class="rounded-2xl bg-white ring-1 ring-ink-950/5 shadow-sm p-5 md:p-6">
                                <span class="font-display text-3xl font-light text-primary-600 tabular-nums" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <p class="mt-3 font-display text-lg font-medium text-ink-950">{{ $factor['title'] }}</p>
                                @if (! empty($factor['description']))
                                    <p class="mt-1.5 text-sm text-neutral-600 leading-relaxed">{{ $factor['description'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                    @if (! empty($block->data['note']))
                        <p class="mt-6 flex items-start gap-2 text-sm text-neutral-600 max-w-2xl reveal">
                            <x-public.icon name="info" class="w-4 h-4 mt-0.5 text-primary-600 shrink-0" />
                            <span>{{ $block->data['note'] }}</span>
                        </p>
                    @endif
                </x-public.section>
            @endif
            @break

        @case('rich_text')
            {{-- Editor HTML is styled by the `.prose` system in app.css
                 (a hand-written, Arabic-tuned set - NOT the Tailwind
                 typography plugin, which is not installed). `prose-reading`
                 steps the body up a size for a page whose job is reading. --}}
            <x-public.section :width="$width">
                <div @class(['prose', 'prose-reading' => $width === 'narrow'])>
                    {!! $block->data['content'] ?? '' !!}
                </div>
            </x-public.section>
            @break

        @case('image')
            @php($img = $media->get($block->data['media_id'] ?? null))
            @if ($img)
                <x-public.section :width="$width">
                    <figure>
                        <img src="{{ $img->url() }}" alt="{{ $img->alt_text ?? '' }}" loading="lazy"
                            class="w-full" width="{{ $img->width }}" height="{{ $img->height }}">
                        @if (! empty($block->data['caption']))
                            <figcaption class="mt-2 text-sm text-neutral-500 text-center">{{ $block->data['caption'] }}</figcaption>
                        @endif
                    </figure>
                </x-public.section>
            @endif
            @break

        @case('gallery')
            @php($images = collect($block->data['media_ids'] ?? [])->map(fn ($id) => $media->get($id))->filter())
            @if ($images->isNotEmpty())
                <x-public.section :width="$width">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-px bg-neutral-200">
                        @foreach ($images as $img)
                            <img src="{{ $img->url() }}" alt="{{ $img->alt_text ?? '' }}" loading="lazy"
                                class="w-full aspect-square object-cover bg-neutral-50">
                        @endforeach
                    </div>
                </x-public.section>
            @endif
            @break

        @case('features')
            @php($items = $block->data['items'] ?? [])
            @if (! empty($items))
                {{-- "What's included" reads as a checklist, not as three
                     feature cards: a ruled two-column list with one small
                     check per row - the only icon that earns its place
                     here, because it is what signals "included". --}}
                <x-public.section>
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 mb-8 text-balance reveal">
                            {{ $block->data['heading'] }}
                        </h2>
                    @endif
                    <ul class="grid md:grid-cols-2 gap-x-12 reveal">
                        @foreach ($items as $item)
                            <li class="flex gap-3 border-b border-neutral-200 py-4">
                                <span class="inline-flex items-center justify-center w-6 h-6 mt-0.5 rounded-full bg-primary-50 text-primary-700 shrink-0" aria-hidden="true"><x-public.icon name="check" class="w-3.5 h-3.5" /></span>
                                <div>
                                    <p class="font-medium text-ink-950">{{ $item['title'] ?? '' }}</p>
                                    @if (! empty($item['description']))
                                        <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ $item['description'] }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-public.section>
            @endif
            @break

        @case('steps')
            @php($items = $block->data['items'] ?? [])
            @if (! empty($items))
                <x-public.section tone="surface">
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 mb-10 text-balance reveal">
                            {{ $block->data['heading'] }}
                        </h2>
                    @endif
                    <ol class="grid sm:grid-cols-2 lg:grid-cols-4 gap-x-10 gap-y-10 reveal">
                        @foreach ($items as $index => $item)
                            <x-public.step-card :number="$index + 1" :title="$item['title'] ?? ''" :description="$item['description'] ?? null" />
                        @endforeach
                    </ol>
                </x-public.section>
            @endif
            @break

        @case('packages')
            @php($items = $block->data['items'] ?? [])
            @if (! empty($items))
                <x-public.section tone="surface">
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950 mb-10">
                            {{ $block->data['heading'] }}
                        </h2>
                    @endif
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                        @foreach ($items as $item)
                            @continue(empty($item['name']) || ! isset($item['price']))
                            <x-public.service-package-card
                                :name="$item['name']"
                                :variant="$item['variant'] ?? null"
                                :price="$item['price']"
                                :previous-price="$item['previous_price'] ?? null"
                                :included-items="array_values(array_filter(preg_split('/\r?\n/', (string) ($item['included_items'] ?? ''))))"
                                :cta-url="$item['cta_url'] ?? null"
                            />
                        @endforeach
                    </div>
                </x-public.section>
            @endif
            @break

        @case('cta')
            @if (! empty($block->data['heading']))
                <x-public.cta :title="$block->data['heading']" :quote-url="route('public.quote')" :whatsapp-url="$block->data['button_url'] ?? null" />
            @endif
            @break

        @case('faq')
            @if ($faqs && $faqs->isNotEmpty())
                <x-public.section tone="surface" :width="$width">
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 mb-6 text-balance">
                            {{ $block->data['heading'] }}
                        </h2>
                    @endif
                    <div class="max-w-2xl divide-y divide-neutral-200 border-t border-neutral-200">
                        @foreach ($faqs as $faq)
                            <x-public.faq-item :question="$faq->question" :answer="$faq->answer" />
                        @endforeach
                    </div>
                </x-public.section>
            @endif
            @break

        @case('related_content')
            @if ($related && $related->isNotEmpty())
                <x-public.section tone="surface">
                    @if (! empty($block->data['heading']))
                        <x-public.section-marker :label="$block->data['heading']" heading class="mb-4" />
                    @endif
                    <ul class="grid md:grid-cols-2 gap-x-12">
                        @foreach ($related as $item)
                            <li class="border-b border-neutral-200">
                                <a href="{{ app(\App\Seo\UrlResolver::class)->urlForPage($item->page) }}" class="group flex items-center justify-between gap-4 py-4 min-h-14">
                                    <span class="min-w-0">
                                        <span class="block font-medium text-ink-950 group-hover:text-primary-700 transition-colors">{{ $item->name }}</span>
                                        @if ($relatedItemType !== 'area' && $item->short_description)
                                            <span class="block mt-0.5 text-sm text-neutral-500 line-clamp-1">{{ $item->short_description }}</span>
                                        @endif
                                        @if ($relatedItemType !== 'area' && ($price = $item->publicPrice()))
                                            <span class="block mt-0.5 text-sm text-ink-950 tabular-nums">{{ $price->label() }}</span>
                                        @endif
                                    </span>
                                    <x-public.icon name="arrow-start" class="w-4 h-4 text-neutral-300 group-hover:text-primary-600 rtl:rotate-180 shrink-0 transition-colors" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-public.section>
            @endif
            @break
    @endswitch
@endforeach
