import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// TECH DEBT (tracked, not yet fixed - revisit before Production):
// The public typeface (IBM Plex Sans Arabic) is loaded via a direct
// <link> to fonts.bunny.net in the layout's <head> instead of through
// this plugin's self-hosting `fonts: [bunny(...)]` option, because this
// development sandbox's Node process cannot complete outbound HTTPS to
// fonts.bunny.net at build time (`curl` succeeds, Node's `fetch` times
// out - a sandbox-specific networking quirk, not a font problem).
// A CDN <link> is an acceptable *development* stand-in, not a final
// Production decision: before going live, re-attempt self-hosting
// (revert to `fonts: [bunny('IBM Plex Sans Arabic', {weights:[...]})]`
// here - see resources/css/app.css's docblock for the matching half of
// this change) so the font is under our own control for performance
// (one origin instead of a third-party DNS/TLS round trip), privacy (no
// visitor request to fonts.bunny.net at all), availability (no
// dependency on Bunny's uptime), and cache behavior (served with our own
// far-future cache headers, versioned by our own asset hash).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
