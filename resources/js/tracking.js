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
    // GA4 (and Google Ads via the linked property) only exists when the
    // analytics settings activate it; gtag is undefined otherwise.
    window.gtag?.('event', type, { page_path: currentPath() });

    // Google Ads conversion for the click, when the ads settings define one
    // (window.vcpAds is printed by the layout only while the tag is active).
    const adsTarget = window.vcpAds?.[type];

    if (adsTarget) {
        window.gtag?.('event', 'conversion', { send_to: adsTarget });
    }

    if (!endpoint || !csrfToken || !navigator.sendBeacon) {
        return;
    }

    const body = new FormData();
    body.append('_token', csrfToken);
    body.append('type', type);
    body.append('path', currentPath());
    navigator.sendBeacon(endpoint, body);
}

const DEFAULT_GREETING = 'مرحبًا، أرغب في الاستفسار';

// The visible page name (its H1 is the page title), so the chat shows the
// customer a readable "الصفحة: ..." line and the team can still trace the
// chat back to the page (App\Support\Tracking\AttributionCode::resolveByName).
function currentPageName() {
    if (currentPath() === '/') {
        return 'الصفحة الرئيسية';
    }

    const name = (document.querySelector('h1')?.textContent ?? '').replace(/\s+/g, ' ').trim();

    return name.length > 0 && name.length <= 80 ? name : '';
}

function withPageName(link) {
    try {
        const url = new URL(link.href);
        const name = currentPageName();
        const text = url.searchParams.get('text') || DEFAULT_GREETING;

        if (name && !text.includes(name)) {
            // encodeURIComponent (%20), matching BusinessProfile::whatsappUrl() -
            // URLSearchParams would write "+", which WhatsApp may show literally.
            url.search = '?text=' + encodeURIComponent(text + '\n' + 'الصفحة: ' + name);
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
        withPageName(link);
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
