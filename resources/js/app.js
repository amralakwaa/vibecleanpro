import Alpine from 'alpinejs';

// Alpine powers the small, real interactions on otherwise server-rendered
// public pages (mobile nav, FAQ disclosure, header scroll state) - see the
// Phase 5 report's "Public Frontend Architecture" section. Livewire is
// intentionally not loaded here: it is reserved for pages with genuine
// server-driven interaction, not for static marketing pages.
window.Alpine = Alpine;
Alpine.start();
