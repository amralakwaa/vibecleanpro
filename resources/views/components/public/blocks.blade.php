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
--}}
@props([
    'blocks',
    'faqs' => null,
    'related' => null,
    'relatedItemType' => null,
    'only' => null,
    'except' => null,
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
        @case('rich_text')
            <x-public.section>
                <div class="prose prose-neutral max-w-none prose-headings:font-bold prose-a:text-primary-600">
                    {!! $block->data['content'] ?? '' !!}
                </div>
            </x-public.section>
            @break

        @case('image')
            @php($img = $media->get($block->data['media_id'] ?? null))
            @if ($img)
                <x-public.section>
                    <figure>
                        <img src="{{ $img->url() }}" alt="{{ $img->alt_text ?? '' }}" loading="lazy"
                            class="w-full rounded-2xl" width="{{ $img->width }}" height="{{ $img->height }}">
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
                <x-public.section>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach ($images as $img)
                            <img src="{{ $img->url() }}" alt="{{ $img->alt_text ?? '' }}" loading="lazy"
                                class="w-full aspect-square object-cover rounded-xl">
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
                <x-public.section tone="surface">
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950 mb-8">
                            {{ $block->data['heading'] }}
                        </h2>
                    @endif
                    <ul class="grid md:grid-cols-2 gap-x-12">
                        @foreach ($items as $item)
                            <li class="flex gap-3 border-b border-neutral-200 py-4">
                                <x-public.icon name="check" class="w-4 h-4 mt-1 text-primary-600 shrink-0" />
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
                <x-public.section>
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950 mb-10">
                            {{ $block->data['heading'] }}
                        </h2>
                    @endif
                    <ol class="grid sm:grid-cols-2 lg:grid-cols-4 gap-x-10 gap-y-10">
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
            <x-public.section>
                <x-public.cta :title="$block->data['heading'] ?? ''" :whatsapp-url="$block->data['button_url'] ?? null" />
            </x-public.section>
            @break

        @case('faq')
            @if ($faqs && $faqs->isNotEmpty())
                <x-public.section tone="surface">
                    @if (! empty($block->data['heading']))
                        <h2 class="font-display text-2xl md:text-3xl font-medium tracking-tight text-ink-950 mb-6">
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
                        <x-public.section-header :title="$block->data['heading']" align="center" class="mb-8" />
                    @endif
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ($related as $item)
                            @if ($relatedItemType === 'area')
                                <x-public.area-card :area="$item" :url="app(\App\Seo\UrlResolver::class)->urlForPage($item->page)" />
                            @else
                                <x-public.service-card :service="$item" :url="app(\App\Seo\UrlResolver::class)->urlForPage($item->page)" />
                            @endif
                        @endforeach
                    </div>
                </x-public.section>
            @endif
            @break
    @endswitch
@endforeach
