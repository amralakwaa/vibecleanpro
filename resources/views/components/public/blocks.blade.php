{{--
    Renders a Page's content_blocks (see app/Filament/Support/ContentBlocks.php
    for the authored shape of each block type) using the design system
    components. This is the ONLY place that interprets block data - page
    templates never hand-render a block's markup themselves.

    'faq' and 'related_content' blocks are placement markers only (no data
    of their own - see ContentBlocks.php's docblock), so the real data for
    them is passed in here rather than re-derived.

    @param \Illuminate\Support\Collection<int, \App\Models\ContentBlock> $blocks
    @param \Illuminate\Support\Collection<int, \App\Models\Faq>|null $faqs
    @param \Illuminate\Support\Collection|null $related
    @param string|null $relatedItemType service|area
--}}
@props(['blocks', 'faqs' => null, 'related' => null, 'relatedItemType' => null])

@php
    $mediaIds = collect();
    foreach ($blocks as $block) {
        $mediaIds->push($block->data['media_id'] ?? null);
        $mediaIds->push($block->data['background_media_id'] ?? null);
        $mediaIds = $mediaIds->merge($block->data['media_ids'] ?? []);
    }
    $media = \App\Models\Media::query()->whereIn('id', $mediaIds->filter()->unique())->get()->keyBy('id');
@endphp

@foreach ($blocks->where('is_active', true) as $block)
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
                <x-public.section tone="surface">
                    @if (! empty($block->data['heading']))
                        <x-public.section-header :title="$block->data['heading']" align="center" class="mb-10" />
                    @endif
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($items as $item)
                            <div class="flex gap-3">
                                <span class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                                    <x-public.icon name="check-circle" class="w-5 h-5" />
                                </span>
                                <div>
                                    <p class="font-semibold text-neutral-900">{{ $item['title'] ?? '' }}</p>
                                    @if (! empty($item['description']))
                                        <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ $item['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-public.section>
            @endif
            @break

        @case('steps')
            @php($items = $block->data['items'] ?? [])
            @if (! empty($items))
                <x-public.section>
                    @if (! empty($block->data['heading']))
                        <x-public.section-header :title="$block->data['heading']" align="center" class="mb-10" />
                    @endif
                    <ol class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($items as $index => $item)
                            <x-public.step-card :number="$index + 1" :title="$item['title'] ?? ''" :description="$item['description'] ?? null" />
                        @endforeach
                    </ol>
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
                        <x-public.section-header :title="$block->data['heading']" align="center" class="mb-8" />
                    @endif
                    <div class="max-w-2xl mx-auto divide-y divide-neutral-200">
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
