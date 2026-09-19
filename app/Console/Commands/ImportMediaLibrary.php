<?php

namespace App\Console\Commands;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Imports the company's own photo library (VibeCleanPro_Media.zip) into the
 * media table, with privacy and descriptions taken from the verified
 * catalogue in database/seeders/media/vibe-library-manifest.json - never
 * inferred from a file name.
 *
 * Idempotent: a file already imported (same disk + path) is left exactly as
 * an editor may have changed it. Held photos always land as private; a photo
 * is ready only with cleared privacy, a verified description and alt text.
 */
class ImportMediaLibrary extends Command
{
    public const DIRECTORY = 'media/vibe';

    protected $signature = 'media:import-library
        {--package=D:/VibeCleanPro_Media.zip : Path to the media package zip}
        {--manifest= : Path to the manifest (defaults to database/seeders/media/vibe-library-manifest.json)}
        {--dry-run : Report what would be imported without writing anything}';

    protected $description = 'Import the verified company photo library with privacy status from the catalogue';

    public function handle(): int
    {
        $manifestPath = $this->option('manifest') ?: database_path('seeders/media/vibe-library-manifest.json');
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $zip = new ZipArchive;

        if ($zip->open((string) $this->option('package')) !== true) {
            $this->components->error('Cannot open the media package: '.$this->option('package'));

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $counts = ['created' => 0, 'kept' => 0, 'missing' => 0, MediaStatus::Ready->value => 0, MediaStatus::Pending->value => 0, MediaStatus::Private->value => 0];

        foreach ($manifest['items'] as $item) {
            $path = self::DIRECTORY.'/'.$item['file'];

            if (Media::query()->where('disk', 'public')->where('path', $path)->exists()) {
                $counts['kept']++;

                continue;
            }

            $bytes = $zip->getFromName($item['file']);

            if ($bytes === false) {
                $counts['missing']++;
                $this->components->warn("Not in package: {$item['file']}");

                continue;
            }

            $attributes = $this->attributesFor($item, $path, $bytes);
            $counts[$attributes['status']->value]++;
            $counts['created']++;

            if ($this->option('dry-run')) {
                continue;
            }

            if (! $disk->exists($path)) {
                $disk->put($path, $bytes);
            }

            Media::query()->create($attributes);
        }

        $zip->close();

        $this->components->info(($this->option('dry-run') ? '[dry run] ' : '').collect($counts)->map(fn (int $count, string $key) => "{$key}: {$count}")->implode(' · '));

        return self::SUCCESS;
    }

    /**
     * @param  array{file: string, original: ?string, privacy_status: string, verified_description: ?string, captured_stage: ?string, source_group: ?string, alt_text: ?string}  $item
     * @return array<string, mixed>
     */
    private function attributesFor(array $item, string $path, string $bytes): array
    {
        $privacy = MediaPrivacyStatus::from($item['privacy_status']);
        [$width, $height] = getimagesizefromstring($bytes) ?: [null, null];

        $status = match (true) {
            $privacy->isHeld() => MediaStatus::Private,
            $privacy === MediaPrivacyStatus::Cleared && filled($item['alt_text']) && filled($item['verified_description']) => MediaStatus::Ready,
            default => MediaStatus::Pending,
        };

        return [
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $item['file'],
            'source_original_name' => $item['original'],
            'mime_type' => str_ends_with($item['file'], '.png') ? 'image/png' : 'image/webp',
            'size' => strlen($bytes),
            'width' => $width,
            'height' => $height,
            'alt_text' => $item['alt_text'],
            'status' => $status,
            'privacy_status' => $privacy,
            'media_type' => MediaType::Real,
            'source' => str_starts_with($item['file'], 'vibe-clean-pro-logo') ? 'brand' : 'vibe_library',
            'source_group' => $item['source_group'],
            'verified_description' => $item['verified_description'],
            'captured_stage' => $item['captured_stage'],
            'content_hash' => hash('sha256', $bytes),
        ];
    }
}
