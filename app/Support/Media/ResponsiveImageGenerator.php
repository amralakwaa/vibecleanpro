<?php

namespace App\Support\Media;

use App\Models\Media;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Writes smaller WebP copies of an uploaded photo so public pages can send
 * phones a 480px image instead of the full upload (srcset). Uses GD only -
 * no extra dependency. Widths at or above the original are skipped: the
 * original itself is always the largest candidate.
 *
 * Resizing goes through imagecopyresampled() rather than imagescale(): the
 * production GD build returns false for IMG_BICUBIC and IMG_BICUBIC_FIXED,
 * which silently produced zero variants for the whole library.
 */
class ResponsiveImageGenerator
{
    /**
     * 768 exists because a 412px phone at DPR 1.75 asks for ~721px: without
     * it the browser jumps to the next candidate up and downloads roughly
     * 1.6x the pixels it will ever paint.
     *
     * @var list<int>
     */
    public const WIDTHS = [480, 768, 1200, 1600];

    private const QUALITY = 76;

    private const DIRECTORY = 'responsive';

    /**
     * @return array<int, string> width => stored path
     */
    public function generate(Media $media): array
    {
        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path) || ! function_exists('imagewebp')) {
            return [];
        }

        $bytes = $disk->get($media->path);
        $dimensions = @getimagesizefromstring($bytes);

        if (! $dimensions || ! $this->fitsInMemory($dimensions[0], $dimensions[1])) {
            return [];
        }

        $source = @imagecreatefromstring($bytes);
        unset($bytes);

        if (! $source instanceof GdImage) {
            return [];
        }

        imagepalettetotruecolor($source);

        $this->delete($media);

        $width = imagesx($source);
        $height = imagesy($source);
        $variants = [];

        foreach (self::WIDTHS as $target) {
            if ($target >= $width) {
                continue;
            }

            $targetHeight = max(1, (int) round($height * $target / $width));
            $resized = imagecreatetruecolor($target, $targetHeight);

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $target, $targetHeight, $width, $height);

            ob_start();
            imagewebp($resized, null, self::QUALITY);
            $path = $this->pathFor($media, $target);
            $disk->put($path, (string) ob_get_clean());
            imagedestroy($resized);

            $variants[$target] = $path;
        }

        imagedestroy($source);

        $media->forceFill(['variants' => $variants ?: null, 'width' => $media->width ?? $width, 'height' => $media->height ?? $height])->saveQuietly();

        return $variants;
    }

    public function delete(Media $media): void
    {
        $paths = array_values($media->variants ?? []);

        if ($paths !== []) {
            Storage::disk($media->disk)->delete($paths);
        }
    }

    /**
     * GD holds a decoded image at ~5 bytes per pixel, plus the largest
     * resized copy. A photo that would not fit under memory_limit is
     * skipped (and logged) rather than taking the request down; it can be
     * processed later with `php -d memory_limit=512M artisan media:generate-variants --missing`.
     */
    private function fitsInMemory(int $width, int $height): bool
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));

        if ($limit <= 0) {
            return true;
        }

        $needed = (int) ($width * $height * 5 * 2);

        if (memory_get_usage(true) + $needed < $limit) {
            return true;
        }

        Log::warning('Responsive sizes skipped: image too large for memory_limit.', ['width' => $width, 'height' => $height]);

        return false;
    }

    private function pathFor(Media $media, int $width): string
    {
        $info = pathinfo($media->path);

        return sprintf('%s/%s/%s-%dw.webp', $info['dirname'], self::DIRECTORY, $info['filename'], $width);
    }
}
