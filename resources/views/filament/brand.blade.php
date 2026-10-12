{{--
    The panel's brand lockup, deliberately the same mark and wordmark the
    public header uses (resources/views/components/public/header.blade.php)
    so the panel reads as the same product rather than a separate tool.

    The mark is decorative here: the wordmark beside it already carries the
    name, so an alt text would make a screen reader announce the brand
    twice. Width/height are the intrinsic pixels of logo-mark.webp, which
    reserve the box before the image decodes and stop the sidebar header
    shifting on load.
--}}
<span class="fi-brand-lockup">
    <img
        src="{{ asset('images/logo-mark.webp') }}"
        alt=""
        aria-hidden="true"
        width="80"
        height="80"
        class="fi-brand-mark"
    >
    <span class="fi-brand-name">{{ filament()->getBrandName() }}</span>
</span>
