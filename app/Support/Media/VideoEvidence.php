<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Cache;

/**
 * The self-hosted field clips under storage/app/public/media/videos.
 *
 * Builds the VideoObject that tells search engines a clip exists: its
 * file, poster, upload date (the file's own timestamp) and duration
 * (read from the MP4's movie header, so nothing is typed by hand). The
 * header read is cached per file version, so a page render never opens
 * the video file.
 */
class VideoEvidence
{
    public static function videoPath(string $slug): string
    {
        return storage_path('app/public/media/videos/'.$slug.'.mp4');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function schema(string $slug, ?string $caption = null): ?array
    {
        $path = self::videoPath($slug);

        if (! is_file($path)) {
            return null;
        }

        $modified = (int) filemtime($path);
        $seconds = Cache::rememberForever("video-evidence:{$slug}:{$modified}", fn (): ?int => self::durationInSeconds($path));
        $root = rtrim((string) config('app.url'), '/');
        $name = $caption ?: 'فيديو من أعمال فايب كلين برو';

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $name,
            'description' => $name.' — لقطة حقيقية من موقع عمل نفّذه فريق فايب كلين برو.',
            'thumbnailUrl' => [$root.'/storage/media/videos/posters/'.$slug.'.webp'],
            'contentUrl' => $root.'/storage/media/videos/'.$slug.'.mp4',
            'uploadDate' => date(DATE_ATOM, $modified),
            'duration' => $seconds ? 'PT'.$seconds.'S' : null,
        ]);
    }

    /**
     * Duration from the MP4 "mvhd" box: timescale and duration, in a
     * version 0 (32-bit) or version 1 (64-bit) layout.
     */
    public static function durationInSeconds(string $path): ?int
    {
        $bytes = @file_get_contents($path);
        $at = $bytes === false ? false : strpos($bytes, 'mvhd');

        if ($at === false) {
            return null;
        }

        $version = ord($bytes[$at + 4]);

        if ($version === 1) {
            $timescale = unpack('N', substr($bytes, $at + 24, 4))[1];
            $duration = unpack('J', substr($bytes, $at + 28, 8))[1];
        } else {
            $timescale = unpack('N', substr($bytes, $at + 16, 4))[1];
            $duration = unpack('N', substr($bytes, $at + 20, 4))[1];
        }

        return $timescale > 0 ? max(1, (int) round($duration / $timescale)) : null;
    }
}
