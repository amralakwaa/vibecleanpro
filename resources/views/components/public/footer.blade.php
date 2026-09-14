{{--
    Footer architecture (Phase 5 report, item 9). Areas are intentionally
    NOT listed here in bulk - "عشرات الأحياء" keyword-stuffed footers are
    explicitly prohibited; $navItems / $legalLinks stay short and real.
--}}
@props([
    'businessProfile',
    'navItems' => [],
    'legalLinks' => [],
    'whatsappUrl' => null,
    'phoneUrl' => null,
])

<footer class="bg-ink-950 text-ink-200">
    <x-public.container width="wide" class="py-14">
        <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <div class="flex items-center gap-2.5 font-bold text-white">
                    @if ($businessProfile?->logo)
                        <img src="{{ $businessProfile->logo->url() }}" alt="{{ $businessProfile->name }}" class="h-8 w-auto">
                    @else
                        <span class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                            <x-public.icon name="sparkles" class="w-4 h-4" />
                        </span>
                    @endif
                    <span>{{ $businessProfile->name ?? config('app.name') }}</span>
                </div>

                @if ($businessProfile?->address || $businessProfile?->city)
                    <p class="mt-4 text-sm leading-relaxed flex items-start gap-2 max-w-xs">
                        <x-public.icon name="map-pin" class="w-4 h-4 mt-0.5 shrink-0" />
                        <span>{{ $businessProfile->address ?? $businessProfile->city }}</span>
                    </p>
                @endif

                @if ($businessProfile?->social_links)
                    <div class="mt-5 flex items-center gap-3">
                        @foreach ($businessProfile->social_links as $social)
                            @if (! empty($social))
                                <a href="{{ $social }}" target="_blank" rel="noopener noreferrer"
                                    class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors">
                                    <x-public.icon name="arrow-start" class="w-4 h-4 rtl:rotate-180" />
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($navItems)
                <div>
                    <p class="text-sm font-semibold text-white mb-3">روابط سريعة</p>
                    <ul class="space-y-2 text-sm">
                        @foreach ($navItems as $label => $url)
                            <li><a href="{{ $url }}" class="hover:text-white transition-colors">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <p class="text-sm font-semibold text-white mb-3">تواصل معنا</p>
                <ul class="space-y-2.5 text-sm">
                    @if ($phoneUrl && $businessProfile?->phone)
                        <li>
                            <a href="{{ $phoneUrl }}" class="flex items-center gap-2 hover:text-white transition-colors">
                                <x-public.icon name="phone" class="w-4 h-4" /> {{ $businessProfile->phone }}
                            </a>
                        </li>
                    @endif
                    @if ($businessProfile?->email)
                        <li>
                            <a href="mailto:{{ $businessProfile->email }}" class="flex items-center gap-2 hover:text-white transition-colors">
                                <x-public.icon name="mail" class="w-4 h-4" /> {{ $businessProfile->email }}
                            </a>
                        </li>
                    @endif
                    @if ($whatsappUrl)
                        <li>
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-white transition-colors">
                                <x-public.icon name="whatsapp" class="w-4 h-4" /> واتساب
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-300/80">
            <p>&copy; {{ now()->year }} {{ $businessProfile->name ?? config('app.name') }}. جميع الحقوق محفوظة.</p>

            @if ($legalLinks)
                <ul class="flex items-center gap-4">
                    @foreach ($legalLinks as $label => $url)
                        <li><a href="{{ $url }}" class="hover:text-white transition-colors">{{ $label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-public.container>
</footer>
