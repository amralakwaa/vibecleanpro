---
paths:
  - app/Console/Commands/GenerateServiceCovers.php
---

# Commands

## Service covers: drawn, not photographed, and never evidence
`php artisan media:service-covers` draws a branded cover (GD, 1600x900 WebP in `media/covers`) for every service with no approved photo, so no service page ships imageless. Rules that hold:
- The covers are `MediaType::Illustration`: they may carry a service hero, but `isEvidence()` is false, and `Media::scopeExcludingLibraryStock()` keeps them out of the project picker. `MediaType::Placeholder` still means "never publishable" - do not blur that line.
- The command never touches a service that already has `featured_media_id`. An editor swaps a cover for a real photo from the panel; nothing about the cover is hardcoded in Blade.
- No text is drawn on a cover. GD cannot shape Arabic, and a broken word on an image is worse than no word.
- There is no image-generation model in this environment (GD only, no Imagick, no API key). Do not promise photorealistic generated imagery; if a photo is needed, it has to be taken.
