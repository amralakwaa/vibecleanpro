{{--
    Footer architecture (Phase 5 report, item 9). Areas are intentionally
    NOT listed here in bulk - "عشرات الأحياء" keyword-stuffed footers are
    explicitly prohibited; $navItems / $legalLinks stay short and real.
--}}
@props([
    'businessProfile',
    'navItems' => [],
    'legalLinks' => [],
    'trustLinks' => [],
    'whatsappUrl' => null,
    'phoneUrl' => null,
])

@once
    {{-- Leaflet is self-hosted (public/vendor/leaflet) rather than pulled
         from a CDN: no render-blocking third-party request on every page and
         no visitor IP handed to a CDN. The stylesheet is injected lazily when
         the footer map nears the viewport (see the script below), so Leaflet's
         CSS, its tiles and its init work all stay off the initial critical
         path - the map is below the fold on every page. Map tiles still load
         from OpenStreetMap at runtime. --}}
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
@endonce

<footer class="bg-ink-950 text-ink-200">
    {{-- Interactive coverage map --}}
    <div class="relative w-full h-48 md:h-60 overflow-hidden" aria-label="خريطة منطقة الخدمة في الرياض" role="img">
        <div id="footer-map" class="absolute inset-0 z-0"></div>
        <script>
            (function () {
                var mapEl = document.getElementById('footer-map');
                if (!mapEl) return;
                var started = false;

                function ensureLeafletCss() {
                    if (document.getElementById('leaflet-css')) return;
                    var link = document.createElement('link');
                    link.id = 'leaflet-css';
                    link.rel = 'stylesheet';
                    link.href = @json(asset('vendor/leaflet/leaflet.css'));
                    document.head.appendChild(link);
                }

                function initMap() {
                    if (started || mapEl._leaflet_id) return;
                    // leaflet.js is deferred; if it has not finished yet, retry shortly.
                    if (typeof L === 'undefined') { setTimeout(initMap, 120); return; }
                    started = true;
                    ensureLeafletCss();
                    var map = L.map('footer-map', {
                        center: [24.7136, 46.6753],
                        zoom: 11,
                        zoomControl: false,
                        attributionControl: false,
                        dragging: false,
                        scrollWheelZoom: false,
                        doubleClickZoom: false,
                        touchZoom: false,
                        keyboard: false,
                    });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
                    L.circle([24.7136, 46.6753], {
                        radius: 26000,
                        color: '#22c55e',
                        fillColor: '#16a34a',
                        fillOpacity: 0.15,
                        weight: 2,
                    }).addTo(map);
                    var icon = L.divIcon({
                        className: '',
                        html: '<div style="background:#16a34a;width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7],
                    });
                    var m = L.marker([24.7136, 46.6753], { icon, title: 'الرياض — منطقة الخدمة' })
                        .addTo(map)
                        .bindTooltip('الرياض — منطقة الخدمة', { permanent: true, direction: 'top', className: 'leaflet-vcp-tooltip' });
                    var el = m.getElement();
                    if (el) { el.setAttribute('aria-label', 'الرياض — منطقة الخدمة'); }
                }
                // Lazy: build the map only when its container nears the viewport,
                // so a visitor who never scrolls to the footer pays nothing for
                // Leaflet's CSS, tiles or init on the initial load.
                if ('IntersectionObserver' in window) {
                    var io = new IntersectionObserver(function (entries) {
                        if (entries[0].isIntersecting) {
                            io.disconnect();
                            initMap();
                        }
                    }, { rootMargin: '300px' });
                    io.observe(mapEl);
                } else if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initMap);
                } else {
                    initMap();
                }
            })();
        </script>
        {{-- dark gradient overlay so the map blends into the footer --}}
        <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-ink-950 to-transparent pointer-events-none z-10" aria-hidden="true"></div>
        {{-- CTA overlay --}}
        @if ($whatsappUrl)
            <a href="{{ $whatsappUrl }}"
               target="_blank" rel="noopener noreferrer"
               class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold px-4 py-2 rounded-full shadow-lg transition-colors whitespace-nowrap"
            >
                <x-public.icon name="map-pin" class="w-3.5 h-3.5" />
                نخدم كامل أحياء الرياض — اطلب الآن
            </a>
        @endif
    </div>

    <style>
        .leaflet-vcp-tooltip {
            background: rgba(22, 163, 74, 0.9);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-family: inherit;
            padding: 3px 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,.3);
            white-space: nowrap;
        }
        .leaflet-vcp-tooltip::before { display: none; }
    </style>

    @php
        $socialIcons = ['tiktok' => 'tiktok', 'facebook' => 'facebook', 'snapchat' => 'snapchat', 'instagram' => 'instagram', 'whatsapp' => 'whatsapp'];
        $socials = collect($businessProfile?->social_links ?? [])
            ->when($whatsappUrl, fn ($c) => $c->has('whatsapp') ? $c : $c->put('whatsapp', $whatsappUrl));
    @endphp

    <x-public.container width="wide" class="pt-16 pb-10">
        {{-- Brand + contact strip --}}
        <div class="flex flex-col items-center text-center mb-12">
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                @if ($businessProfile?->logo)
                    <img src="{{ $businessProfile->logo->url() }}" alt="{{ $businessProfile?->displayName() }}" class="h-10 w-auto">
                @endif
                <span class="font-display font-bold text-xl text-white group-hover:text-primary-300 transition-colors">{{ $businessProfile?->displayName() ?: config('app.name') }}</span>
            </a>

            @if ($businessProfile?->identity_statement)
                <p class="mt-3 text-sm text-ink-300 max-w-sm leading-relaxed">{{ $businessProfile->identity_statement }}</p>
            @endif

            @if ($businessProfile?->address || $businessProfile?->city)
                <p class="mt-3 text-sm text-ink-300 flex items-center gap-1.5">
                    <x-public.icon name="map-pin" class="w-3.5 h-3.5 shrink-0" />
                    {{ $businessProfile->address ?? $businessProfile->city }}
                </p>
            @endif

            @if ($socials->filter()->isNotEmpty())
                <ul class="mt-5 flex items-center gap-2.5">
                    @foreach ($socials as $platform => $url)
                        @if (! empty($url))
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/[0.07] text-ink-300 hover:bg-primary-600 hover:text-white hover:scale-110 transition-all duration-200"
                                    aria-label="{{ $platform }}">
                                    <x-public.icon :name="$socialIcons[strtolower($platform)] ?? 'arrow-start'" class="w-[1.15rem] h-[1.15rem]" />
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Divider --}}
        <div class="h-px bg-gradient-to-l from-transparent via-white/15 to-transparent mb-10" aria-hidden="true"></div>

        {{-- Links grid --}}
        <div class="grid gap-10 sm:grid-cols-2 md:grid-cols-3">
            @if ($navItems)
                <div>
                    <p class="text-[0.7rem] font-semibold tracking-widest text-ink-400 uppercase mb-4">روابط سريعة</p>
                    <ul class="space-y-2.5 text-sm">
                        @foreach ($navItems as $label => $url)
                            <li>
                                <a href="{{ $url }}" class="inline-flex items-center gap-1.5 text-ink-200 hover:text-white hover:translate-x-[-2px] transition-all duration-150">
                                    <span class="w-1 h-1 rounded-full bg-primary-500/60 shrink-0"></span>
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($trustLinks)
                <div>
                    <p class="text-[0.7rem] font-semibold tracking-widest text-ink-400 uppercase mb-4">الثقة والسياسات</p>
                    <ul class="space-y-2.5 text-sm">
                        @foreach ($trustLinks as $label => $url)
                            <li>
                                <a href="{{ $url }}" class="inline-flex items-center gap-1.5 text-ink-200 hover:text-white hover:translate-x-[-2px] transition-all duration-150">
                                    <span class="w-1 h-1 rounded-full bg-primary-500/60 shrink-0"></span>
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <p class="text-[0.7rem] font-semibold tracking-widest text-ink-400 uppercase mb-4">تواصل معنا</p>
                <ul class="space-y-3 text-sm">
                    @if ($phoneUrl && $businessProfile?->phone)
                        <li>
                            <a href="{{ $phoneUrl }}" class="flex items-center gap-3 py-2 px-3 -mx-3 rounded-lg hover:bg-white/[0.05] transition-colors group">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/[0.07] text-ink-300 group-hover:bg-primary-600 group-hover:text-white transition-colors shrink-0">
                                    <x-public.icon name="phone" class="w-4 h-4" />
                                </span>
                                <span dir="ltr" class="text-ink-200 group-hover:text-white transition-colors">{{ $businessProfile->phone }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($businessProfile?->email)
                        <li>
                            <a href="mailto:{{ $businessProfile->email }}" class="flex items-center gap-3 py-2 px-3 -mx-3 rounded-lg hover:bg-white/[0.05] transition-colors group">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/[0.07] text-ink-300 group-hover:bg-primary-600 group-hover:text-white transition-colors shrink-0">
                                    <x-public.icon name="mail" class="w-4 h-4" />
                                </span>
                                <span class="text-ink-200 group-hover:text-white transition-colors">{{ $businessProfile->email }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($whatsappUrl)
                        <li>
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 py-2 px-3 -mx-3 rounded-lg hover:bg-white/[0.05] transition-colors group">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/[0.07] text-ink-300 group-hover:bg-success-600 group-hover:text-white transition-colors shrink-0">
                                    <x-public.icon name="whatsapp" class="w-4 h-4" />
                                </span>
                                <span class="text-ink-200 group-hover:text-white transition-colors">واتساب</span>
                            </a>
                        </li>
                    @endif
                    @if ($gbpUrl = $businessProfile?->publicGoogleBusinessProfileUrl())
                        <li>
                            <a href="{{ $gbpUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 py-2 px-3 -mx-3 rounded-lg hover:bg-white/[0.05] transition-colors group">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/[0.07] text-ink-300 group-hover:bg-primary-600 group-hover:text-white transition-colors shrink-0">
                                    <x-public.icon name="map-pin" class="w-4 h-4" />
                                </span>
                                <span class="text-ink-200 group-hover:text-white transition-colors">خرائط Google</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-12 pt-6 border-t border-white/[0.07] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-400">
            <p>
                &copy; {{ now()->year }} {{ $businessProfile?->displayName() ?: config('app.name') }}. جميع الحقوق محفوظة.
                @if ($registration = $businessProfile?->publicCommercialRegistration())
                    <span class="ms-2">السجل التجاري: <span dir="ltr">{{ $registration }}</span></span>
                @endif
            </p>

            @if ($legalLinks)
                <ul class="flex items-center gap-4">
                    @foreach ($legalLinks as $label => $url)
                        <li><a href="{{ $url }}" class="hover:text-white transition-colors">{{ $label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if (filled($businessProfile?->credit_name))
            <div class="mt-6 pt-6 pb-24 lg:pb-0 border-t border-white/[0.07] flex flex-col items-center gap-2 text-center">
                <p class="text-sm text-ink-400">
                    تصميم وتطوير
                    <span class="font-display font-semibold text-transparent bg-clip-text bg-gradient-to-l from-primary-300 to-primary-500">{{ $businessProfile->credit_name }}</span>
                </p>
                @if (filled($businessProfile->credit_phone))
                    <a href="tel:{{ preg_replace('/\s+/', '', $businessProfile->credit_phone) }}"
                       class="inline-flex items-center gap-2 rounded-full bg-primary-500/15 ring-1 ring-primary-400/30 px-4 py-1.5 text-sm font-semibold text-primary-200 hover:bg-primary-500/25 hover:text-white transition-colors">
                        <x-public.icon name="phone" class="w-4 h-4" />
                        <span dir="ltr">{{ $businessProfile->credit_phone }}</span>
                    </a>
                @endif
            </div>
        @endif
    </x-public.container>
</footer>
