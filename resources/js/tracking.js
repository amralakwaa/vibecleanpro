// Conversion tracking (see 00_EXECUTION/CONVERSION_TRACKING_PLAN.md).
// One delegated listener covers every WhatsApp and phone link on every
// page - including links editors type into content blocks - so no CTA can
// ship untracked. The link itself is never delayed: sendBeacon is queued
// by the browser and survives the navigation away.

const endpoint = document.querySelector('meta[name="vcp-track"]')?.content;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

function currentPath() {
    return '/' + window.location.pathname.replace(/^\/+|\/+$/g, '');
}

function send(type) {
    if (!endpoint || !csrfToken || !navigator.sendBeacon) {
        return;
    }

    const body = new FormData();
    body.append('_token', csrfToken);
    body.append('type', type);
    body.append('path', currentPath());
    navigator.sendBeacon(endpoint, body);
}

// FNV-1a 32-bit, identical to App\Support\Tracking\AttributionCode::forPath.
export function attributionCode(path) {
    let hash = 0x811c9dc5;

    for (const byte of new TextEncoder().encode(path)) {
        hash = Math.imul(hash ^ byte, 0x01000193) >>> 0;
    }

    return 'V-' + hash.toString(36).toUpperCase().padStart(7, '0');
}

const DEFAULT_GREETING = 'مرحبًا، أرغب في الاستفسار';

function withAttributionCode(link) {
    try {
        const url = new URL(link.href);
        const code = attributionCode(currentPath());
        const text = url.searchParams.get('text') || DEFAULT_GREETING;

        if (!text.includes(code)) {
            // encodeURIComponent (%20), matching BusinessProfile::whatsappUrl() -
            // URLSearchParams would write "+", which WhatsApp may show literally.
            url.search = '?text=' + encodeURIComponent(text + '\n' + 'رمز الصفحة: ' + code);
            link.href = url.toString();
        }
    } catch {
        // A malformed editor-entered link still navigates untouched.
    }
}

document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');

    if (!link) {
        return;
    }

    const href = link.getAttribute('href');

    if (/^https:\/\/(wa\.me|api\.whatsapp\.com)\//i.test(href)) {
        withAttributionCode(link);
        send('whatsapp_click');
    } else if (/^tel:/i.test(href)) {
        send('phone_click');
    }
});

document.querySelectorAll('form[data-track-form]').forEach((form) => {
    form.addEventListener('focusin', () => {
        const type = form.dataset.trackForm;
        const key = 'vcp:' + type + ':' + currentPath();

        try {
            if (sessionStorage.getItem(key)) {
                return;
            }
            sessionStorage.setItem(key, '1');
        } catch {
            // Private mode without storage: the server-side dedupe still applies.
        }

        send(type);
    }, { once: true });
});
