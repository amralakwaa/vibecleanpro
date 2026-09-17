<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imports the initial media library: licensed stock illustrations for the
 * homepage hero and the service catalogue, described in
 * database/seeders/media/manifest.json (source, photographer and licence
 * for every file live there).
 *
 * Reproducible and idempotent - safe to run on a fresh install and again
 * at any time (`php artisan db:seed --class=InitialMediaSeeder`):
 *
 *  - a file is copied to the public disk under media/library/ only when
 *    it is missing there;
 *  - a Media record is created once per file (matched by disk + path);
 *    an existing record keeps whatever an editor changed - alt text and
 *    caption are never overwritten;
 *  - a service with no featured image gets the matching library image
 *    (by page slug, then by keywords in its name); a service that already
 *    has one is left alone;
 *  - the homepage hero setting is filled only while it is empty.
 *
 * It never touches projects: stock photography must not appear as the
 * company's own work, so nothing here attaches media to project_media.
 */
class InitialMediaSeeder extends Seeder
{
    public function run(): void
    {
        $manifest = self::manifest();
        $disk = Storage::disk('public');
        $disk->makeDirectory(Media::LIBRARY_DIRECTORY);

        /** @var array<string, Media> $library */
        $library = [];

        foreach ($manifest['items'] as $item) {
            $library[$item['key']] = $this->importItem($item);
        }

        $this->assignServiceImages($manifest['items'], $library);
        $this->assignHero($manifest['items'], $library);
    }

    /**
     * @return array{library_directory: string, items: array<int, array{key: string, file: string, role: string, alt_text: string, assign: array{service_slugs: array<int, string>, name_keywords: array<int, string>}, source: array<string, mixed>}>}
     */
    public static function manifest(): array
    {
        return json_decode((string) file_get_contents(self::sourceDirectory().'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function sourceDirectory(): string
    {
        return database_path('seeders/media');
    }

    /**
     * @param  array{key: string, file: string, alt_text: string}  $item
     */
    private function importItem(array $item): Media
    {
        $disk = Storage::disk('public');
        $source = self::sourceDirectory().'/'.$item['file'];
        $path = Media::LIBRARY_DIRECTORY.'/'.$item['file'];

        if (! $disk->exists($path)) {
            $disk->put($path, (string) file_get_contents($source));
        }

        $existing = Media::query()->where('disk', 'public')->where('path', $path)->first();

        if ($existing) {
            return $existing;
        }

        [$width, $height] = getimagesize($source) ?: [null, null];

        return Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $item['file'],
            'mime_type' => 'image/jpeg',
            'size' => filesize($source) ?: null,
            'width' => $width,
            'height' => $height,
            'alt_text' => $item['alt_text'],
        ]);
    }

    /**
     * @param  array<int, array{key: string, role: string, assign: array{service_slugs: array<int, string>, name_keywords: array<int, string>}}>  $items
     * @param  array<string, Media>  $library
     */
    private function assignServiceImages(array $items, array $library): void
    {
        $candidates = collect($items)->where('role', 'service')->values();

        Service::query()
            ->whereNull('featured_media_id')
            ->with('page')
            ->get()
            ->each(function (Service $service) use ($candidates, $library) {
                $slug = $service->page?->slug;
                $name = Str::lower($service->name);

                $match = $candidates->first(fn (array $item) => $slug && in_array($slug, $item['assign']['service_slugs'], true))
                    ?? $candidates->first(fn (array $item) => collect($item['assign']['name_keywords'])->contains(fn (string $keyword) => Str::contains($name, Str::lower($keyword))));

                if ($match) {
                    $service->update(['featured_media_id' => $library[$match['key']]->id]);
                }
            });
    }

    /**
     * @param  array<int, array{key: string, role: string}>  $items
     * @param  array<string, Media>  $library
     */
    private function assignHero(array $items, array $library): void
    {
        $hero = collect($items)->firstWhere('role', 'hero');

        if (! $hero || SiteSetting::get(SiteSetting::HOME_HERO_MEDIA_ID)) {
            return;
        }

        SiteSetting::query()->updateOrCreate(
            ['key' => SiteSetting::HOME_HERO_MEDIA_ID],
            ['value' => (string) $library[$hero['key']]->id, 'type' => 'int'],
        );
    }
}
