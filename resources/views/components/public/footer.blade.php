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
         no visitor IP handed to a CDN. Map tiles still load from OpenStreetMap
         at runtime. --}}
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
@endonce

<footer class="bg-ink-950 text-ink-200">
    {{-- Interactive coverage map --}}
    <div class="relative w-full h-48 md:h-60 overflow-hidden" aria-label="خريطة منطقة الخدمة في الرياض" role="img">
        <div id="footer-map" class="absolute inset-0 z-0"></div>
        <script>
            (function () {
                function initMap() {
                    if (typeof L === 'undefined' || document.getElementById('footer-map')._leaflet_id) return;
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
                    L.marker([24.7136, 46.6753], { icon })
                        .addTo(map)
                        .bindTooltip('الرياض — منطقة الخدمة', { permanent: true, direction: 'top', className: 'leaflet-vcp-tooltip' });
                }
                if (document.readyState === 'loading') {
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

    <x-public.container width="wide" class="py-14">
        <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
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
                    {{-- The platform name the editor typed is the link
                         text: every social link is named, and no brand
                         icon is faked with a generic arrow. --}}
                    <ul class="mt-5 flex flex-wrap items-center gap-2">
                        @foreach ($businessProfile->social_links as $platform => $social)
                            @if (! empty($social))
                                <li>
                                    <a href="{{ $social }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center min-h-11 px-3.5 rounded-full bg-white/10 text-sm text-ink-100 hover:bg-white/20 hover:text-white transition-colors">
                                        {{ is_string($platform) ? $platform : $social }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
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

            @if ($trustLinks)
                <div>
                    <p class="text-sm font-semibold text-white mb-3">الثقة والسياسات</p>
                    <ul class="space-y-2 text-sm">
                        @foreach ($trustLinks as $label => $url)
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
            <p>
                &copy; {{ now()->year }} {{ $businessProfile->name ?? config('app.name') }}. جميع الحقوق محفوظة.
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
    </x-public.container>
</footer>
