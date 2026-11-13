{{--
    Editor-managed content under a controller-rendered index (/contact,
    /offers, /areas). See App\Support\Content\IndexCompanion: the companion is
    a published Landing page whose slug is the index path. Renders nothing
    when no companion is published.
--}}
@if ($companion ?? null)
    <x-public.blocks :blocks="$companion->contentBlocks" :except="['faq']" />
    <x-public.blocks :blocks="$companion->contentBlocks" :only="['faq']" :faqs="$companion->faqs" />
@endif
