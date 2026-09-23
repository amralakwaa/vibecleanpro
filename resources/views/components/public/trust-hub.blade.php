{{--
    The /trust hub body: the Trust Center's index. Every policy in the
    family as a card, so a visitor lands here and moves out to exactly the
    one they need. Titles are the real DB titles; icon/eyebrow/blurb are the
    family's navigation metadata (TrustPolicies). This supersedes the hub's
    plain author-built link list with the designed card family, and adds no
    claim of its own.

    @param array<string, array{icon: string, eyebrow: string, blurb: string, accent: string}> $policies
    @param \Illuminate\Support\Collection<string, string> $titles  slug => page title
--}}
@props(['policies', 'titles'])

<section class="relative isolate bg-background overflow-hidden">
    <div class="glow-primary absolute -top-24 -start-24 w-[24rem] h-[24rem] opacity-30" aria-hidden="true"></div>

    <x-public.container width="wide" class="relative py-14 md:py-20">
        <div class="max-w-2xl reveal">
            <p class="text-sm font-medium tracking-wide text-primary-700">ابدأ من هنا</p>
            <h2 class="mt-2 font-display text-2xl md:text-4xl md:leading-[1.15] font-medium tracking-tight text-ink-950 text-balance">اختر السياسة التي تحتاجها</h2>
            <p class="mt-3 text-neutral-600 leading-relaxed">كل ما يخص حقوقك، نطاق الخدمة، والتزاماتنا تجاهك — بلغة واضحة ومكان واحد.</p>
        </div>

        <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 reveal">
            @foreach ($policies as $slug => $meta)
                @php($title = $titles[$slug] ?? null)
                @continue(! $title)
                <li>
                    <x-public.trust-policy-card
                        :url="url('/'.$slug)"
                        :title="$title"
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
