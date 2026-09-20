<?php

namespace App\Console\Commands;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Media;
use App\Models\Service;
use GdImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Draws a branded cover for every service that has no photograph yet, so
 * no service page ever ships without an image.
 *
 * These are DESIGNED covers, not photographs and not AI imagery: a brand
 * gradient with a flat pictogram of the service. They are registered as
 * MediaType::Illustration, which can never stand as evidence of our work
 * (see MediaType::isEvidence), and they carry an alt text that says what
 * they are. The moment a real photo is approved, a Media
 * Manager points the service at it from the panel and the cover is gone -
 * this command never overwrites a service that already has an image.
 */
class GenerateServiceCovers extends Command
{
    protected $signature = 'media:service-covers {--force : redraw covers that already exist}';

    protected $description = 'Draw a branded cover for every service that has no approved photo yet';

    public const DIRECTORY = 'media/covers';

    public const WIDTH = 1600;

    public const HEIGHT = 900;

    /**
     * slug => [motif, top colour, bottom colour, accent].
     * Each service gets its own hue so the cards never look like one
     * repeated tile, while staying inside the brand's blue/ink range.
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public const COVERS = [
        'apartment-cleaning' => ['apartment', '#1e3a8a', '#2563eb', '#bfd7fe'],
        'majlis-cleaning' => ['majlis', '#16233d', '#1d4fd8', '#93bbfd'],
        'shop-cleaning' => ['shop', '#0c1830', '#1e40af', '#dbe8fe'],
        'marble-polishing' => ['marble', '#243350', '#3b7cf6', '#eff5ff'],
        'pool-cleaning' => ['pool', '#072554', '#2563eb', '#bfd7fe'],
        'pest-control' => ['shield', '#071228', '#1e40af', '#93bbfd'],
        'cleaning-contracts' => ['calendar', '#16233d', '#2563eb', '#dbe8fe'],
        'courtyard-cleaning' => ['pavers', '#0c1830', '#1d4fd8', '#bfd7fe'],
        'glass-cleaning' => ['window', '#102a4f', '#2563eb', '#eff5ff'],
        'sofa-cleaning' => ['sofa', '#1e3a8a', '#3b7cf6', '#eff5ff'],
        'water-tank-cleaning' => ['tank', '#072554', '#1d4fd8', '#93bbfd'],
        'disinfection' => ['spray', '#0c1830', '#2563eb', '#dbe8fe'],
    ];

    /**
     * Arabic alt text per motif. It describes the drawing, never claims a
     * site, a team or a result.
     *
     * @var array<string, string>
     */
    private const ALT = [
        'apartment-cleaning' => 'غلاف خدمة تنظيف الشقق من فايب كلين برو — رسم توضيحي لمبنى سكني بهوية العلامة',
        'majlis-cleaning' => 'غلاف خدمة تنظيف المجالس من فايب كلين برو — رسم توضيحي لمجلس بهوية العلامة',
        'shop-cleaning' => 'غلاف خدمة تنظيف المحلات والمعارض من فايب كلين برو — رسم توضيحي لواجهة محل',
        'marble-polishing' => 'غلاف خدمة جلي وتلميع الرخام من فايب كلين برو — رسم توضيحي لأرضية لامعة',
        'pool-cleaning' => 'غلاف خدمة تنظيف المسابح من فايب كلين برو — رسم توضيحي لحوض مسبح',
        'pest-control' => 'غلاف خدمة مكافحة الحشرات من فايب كلين برو — رسم توضيحي لدرع حماية',
        'cleaning-contracts' => 'غلاف خدمة عقود النظافة الدورية من فايب كلين برو — رسم توضيحي لجدول زيارات',
        'courtyard-cleaning' => 'غلاف خدمة تنظيف الأحواش والممرات من فايب كلين برو — رسم توضيحي لبلاط انترلوك',
        'glass-cleaning' => 'غلاف خدمة تنظيف الزجاج من فايب كلين برو — رسم توضيحي لنافذة ذات ألواح زجاجية',
        'sofa-cleaning' => 'غلاف خدمة تنظيف الكنب من فايب كلين برو — رسم توضيحي لكنبة',
        'water-tank-cleaning' => 'غلاف خدمة تنظيف خزانات المياه من فايب كلين برو — رسم توضيحي لخزان مياه',
        'disinfection' => 'غلاف خدمة التعقيم والتطهير من فايب كلين برو — رسم توضيحي لأداة رش وقطرات',
    ];

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('GD has no WebP support - cannot draw covers.');

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $drawn = 0;
        $attached = 0;

        foreach (self::COVERS as $slug => [$motif, $from, $to, $accent]) {
            $service = Service::query()->whereHas('page', fn ($query) => $query->where('slug', $slug))->with('page')->first();

            if (! $service) {
                $this->warn("  {$slug}: no such service - skipped.");

                continue;
            }

            $path = self::DIRECTORY.'/'.$slug.'-cover.webp';

            if (! $disk->exists($path) || $this->option('force')) {
                $disk->put($path, $this->draw($motif, $from, $to, $accent));
                $drawn++;
            }

            $media = Media::query()->firstOrNew(['path' => $path]);
            $media->forceFill([
                'disk' => 'public',
                'path' => $path,
                'original_filename' => basename($path),
                'mime_type' => 'image/webp',
                'size' => $disk->size($path),
                'width' => self::WIDTH,
                'height' => self::HEIGHT,
                'alt_text' => self::ALT[$slug],
                'verified_description' => 'غلاف مصمَّم بهوية فايب كلين برو لخدمة '.$service->name.'. ليس صورة فوتوغرافية ولا توثيقًا لعمل نُفِّذ، ويُستبدل بصورة حقيقية من لوحة التحكم متى توفّرت.',
                // Illustration, never Real: it may carry a service page
                // that has no photo yet, but MediaType::isEvidence() keeps
                // it out of project galleries and before/after pairs.
                'media_type' => MediaType::Illustration,
                'privacy_status' => MediaPrivacyStatus::Cleared,
                'status' => MediaStatus::Ready,
                'source' => 'generated',
                'source_group' => 'service-covers',
            ])->save();

            // Never replace a photograph an editor chose.
            if (blank($service->featured_media_id)) {
                $service->update(['featured_media_id' => $media->id]);
                $attached++;
            }

            $this->line("  {$slug}: cover ready (media #{$media->id}).");
        }

        $this->info("Drew {$drawn} cover(s), attached {$attached} to services without an image.");

        return self::SUCCESS;
    }

    /**
     * Renders one cover and returns the WebP bytes.
     */
    public function draw(string $motif, string $from, string $to, string $accent): string
    {
        $canvas = $this->gradient($from, $to);

        // Two soft blooms, mostly off-canvas, so the pictogram stays the
        // subject and the card never reads as a white blob.
        $this->glow($canvas, 1520, 60, 820, 125);
        $this->glow($canvas, 120, 880, 560, 126);

        $ink = $this->allocate($canvas, $accent, 92);
        $soft = $this->allocate($canvas, $accent, 40);
        $solid = $this->allocate($canvas, $accent, 118);

        match ($motif) {
            'apartment' => $this->apartment($canvas, $ink, $soft, $solid),
            'majlis' => $this->majlis($canvas, $ink, $soft, $solid),
            'shop' => $this->shop($canvas, $ink, $soft, $solid),
            'marble' => $this->marble($canvas, $ink, $soft, $solid),
            'pool' => $this->pool($canvas, $ink, $soft, $solid),
            'shield' => $this->shield($canvas, $ink, $soft, $solid),
            'calendar' => $this->calendar($canvas, $ink, $soft, $solid),
            'pavers' => $this->pavers($canvas, $ink, $soft, $solid),
            'window' => $this->window($canvas, $ink, $soft, $solid),
            'sofa' => $this->sofa($canvas, $ink, $soft, $solid),
            'tank' => $this->tank($canvas, $ink, $soft, $solid),
            'spray' => $this->spray($canvas, $ink, $soft, $solid),
            default => null,
        };

        $this->brandMark($canvas, $accent);

        ob_start();
        imagewebp($canvas, null, 82);
        $bytes = (string) ob_get_clean();
        imagedestroy($canvas);

        return $bytes;
    }

    /**
     * A smooth diagonal gradient: drawn small, then resampled up, which is
     * both smoother and far cheaper than a per-pixel loop at full size.
     */
    private function gradient(string $from, string $to): GdImage
    {
        $small = imagecreatetruecolor(160, 90);
        [$r1, $g1, $b1] = $this->rgb($from);
        [$r2, $g2, $b2] = $this->rgb($to);

        for ($y = 0; $y < 90; $y++) {
            for ($x = 0; $x < 160; $x++) {
                $t = min(1.0, max(0.0, ($x / 160 * 0.45) + ($y / 90 * 0.55)));
                $colour = imagecolorallocate(
                    $small,
                    (int) round($r1 + ($r2 - $r1) * $t),
                    (int) round($g1 + ($g2 - $g1) * $t),
                    (int) round($b1 + ($b2 - $b1) * $t),
                );
                imagesetpixel($small, $x, $y, $colour);
            }
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled($canvas, $small, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, 160, 90);
        imagedestroy($small);
        imagealphablending($canvas, true);

        return $canvas;
    }

    /**
     * A soft light bloom, built from concentric translucent ellipses.
     */
    private function glow(GdImage $canvas, int $x, int $y, int $radius, int $alpha): void
    {
        for ($step = $radius; $step > 0; $step -= 8) {
            $colour = imagecolorallocatealpha($canvas, 255, 255, 255, $alpha);
            imagefilledellipse($canvas, $x, $y, $step, $step, $colour);
        }
    }

    private function apartment(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        $x = 560;
        $y = 210;
        imagefilledrectangle($canvas, $x, $y, $x + 300, $y + 480, $soft);
        imagefilledrectangle($canvas, $x + 320, $y + 120, $x + 520, $y + 480, $soft);

        for ($row = 0; $row < 5; $row++) {
            for ($column = 0; $column < 3; $column++) {
                imagefilledrectangle($canvas, $x + 34 + $column * 90, $y + 44 + $row * 86, $x + 34 + $column * 90 + 54, $y + 44 + $row * 86 + 54, $ink);
            }
        }

        for ($row = 0; $row < 3; $row++) {
            for ($column = 0; $column < 2; $column++) {
                imagefilledrectangle($canvas, $x + 356 + $column * 92, $y + 168 + $row * 100, $x + 356 + $column * 92 + 56, $y + 168 + $row * 100 + 56, $solid);
            }
        }
    }

    private function majlis(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledarc($canvas, 800, 470, 520, 520, 180, 360, $soft, IMG_ARC_PIE);
        imagefilledrectangle($canvas, 540, 470, 1060, 520, $soft);

        for ($seat = 0; $seat < 4; $seat++) {
            $x = 578 + $seat * 122;
            imagefilledrectangle($canvas, $x, 528, $x + 96, 592, $ink);
            imagefilledrectangle($canvas, $x + 12, 470, $x + 84, 522, $solid);
        }

        imagefilledrectangle($canvas, 540, 596, 1060, 620, $solid);
    }

    private function shop(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledrectangle($canvas, 560, 300, 1040, 660, $soft);

        for ($stripe = 0; $stripe < 8; $stripe++) {
            $x = 560 + $stripe * 60;
            imagefilledpolygon($canvas, [$x, 300, $x + 60, 300, $x + 44, 372, $x + 16, 372], $stripe % 2 === 0 ? $ink : $solid);
        }

        imagefilledrectangle($canvas, 604, 420, 820, 600, $ink);
        imagefilledrectangle($canvas, 860, 420, 1000, 660, $solid);
    }

    private function marble(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        for ($row = 0; $row < 4; $row++) {
            for ($column = 0; $column < 5; $column++) {
                $inset = $row * 26;
                $x = 480 + $column * 130 + $inset;
                $y = 330 + $row * 94;
                imagefilledpolygon($canvas, [$x, $y, $x + 118, $y, $x + 118 - 22, $y + 82, $x - 22, $y + 82], ($row + $column) % 2 === 0 ? $soft : $ink);
            }
        }

        imagefilledpolygon($canvas, [700, 300, 820, 300, 640, 700, 520, 700], $solid);
    }

    private function pool(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledrectangle($canvas, 520, 360, 1080, 660, $soft);

        for ($wave = 0; $wave < 4; $wave++) {
            $y = 420 + $wave * 62;
            for ($x = 540; $x < 1060; $x += 80) {
                imagefilledarc($canvas, $x, $y, 80, 36, 0, 180, $wave % 2 === 0 ? $ink : $solid, IMG_ARC_PIE);
            }
        }

        imagesetthickness($canvas, 14);
        imageline($canvas, 1010, 330, 1010, 470, $solid);
        imageline($canvas, 1060, 330, 1060, 470, $solid);
        imageline($canvas, 1010, 378, 1060, 378, $solid);
        imagesetthickness($canvas, 1);
    }

    private function shield(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledpolygon($canvas, [800, 250, 1020, 340, 1020, 520, 800, 660, 580, 520, 580, 340], $soft);
        imagefilledpolygon($canvas, [800, 320, 960, 384, 960, 510, 800, 596, 640, 510, 640, 384], $ink);

        foreach ([[720, 420], [880, 420], [800, 500]] as [$x, $y]) {
            imagefilledellipse($canvas, $x, $y, 54, 54, $solid);
        }
    }

    private function calendar(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledrectangle($canvas, 560, 280, 1040, 660, $soft);
        imagefilledrectangle($canvas, 560, 280, 1040, 348, $solid);

        for ($row = 0; $row < 3; $row++) {
            for ($column = 0; $column < 5; $column++) {
                $x = 600 + $column * 86;
                $y = 388 + $row * 88;
                imagefilledrectangle($canvas, $x, $y, $x + 58, $y + 58, ($row * 5 + $column) % 4 === 0 ? $solid : $ink);
            }
        }
    }

    private function pavers(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        for ($row = 0; $row < 6; $row++) {
            $offset = $row % 2 === 0 ? 0 : 56;
            for ($column = 0; $column < 6; $column++) {
                $x = 460 + $column * 112 + $offset;
                $y = 300 + $row * 64;
                imagefilledrectangle($canvas, $x, $y, $x + 96, $y + 48, ($row + $column) % 3 === 0 ? $solid : (($row + $column) % 3 === 1 ? $ink : $soft));
            }
        }
    }

    /**
     * A four-pane window with a squeegee streak across it: the subject of
     * this service is the glass itself, not a building.
     */
    private function window(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledrectangle($canvas, 600, 260, 1000, 660, $soft);

        foreach ([[624, 284, 792, 456], [808, 284, 976, 456], [624, 472, 792, 636], [808, 472, 976, 636]] as [$x1, $y1, $x2, $y2]) {
            imagefilledrectangle($canvas, $x1, $y1, $x2, $y2, $ink);
        }

        // The clean streak: a diagonal band left behind by the blade.
        imagefilledpolygon($canvas, [660, 636, 812, 284, 884, 284, 732, 636], $solid);

        imagefilledrectangle($canvas, 560, 676, 1040, 692, $solid);
    }

    private function sofa(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledrectangle($canvas, 540, 380, 1060, 520, $soft);
        imagefilledrectangle($canvas, 500, 440, 1100, 610, $ink);
        imagefilledrectangle($canvas, 500, 440, 570, 610, $solid);
        imagefilledrectangle($canvas, 1030, 440, 1100, 610, $solid);

        for ($cushion = 0; $cushion < 3; $cushion++) {
            $x = 596 + $cushion * 142;
            imagefilledrectangle($canvas, $x, 396, $x + 118, 500, $solid);
        }

        imagefilledrectangle($canvas, 540, 610, 580, 668, $soft);
        imagefilledrectangle($canvas, 1020, 610, 1060, 668, $soft);
    }

    private function tank(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledrectangle($canvas, 620, 330, 980, 640, $soft);
        imagefilledellipse($canvas, 800, 330, 360, 120, $ink);
        imagefilledellipse($canvas, 800, 640, 360, 120, $ink);
        imagefilledrectangle($canvas, 620, 470, 980, 640, $solid);
        imagefilledellipse($canvas, 800, 470, 360, 120, $solid);
        imagefilledpolygon($canvas, [800, 196, 852, 288, 748, 288], $solid);
        imagefilledellipse($canvas, 800, 296, 104, 104, $solid);
    }

    private function spray(GdImage $canvas, int $ink, int $soft, int $solid): void
    {
        imagefilledrectangle($canvas, 700, 380, 880, 660, $soft);
        imagefilledrectangle($canvas, 744, 300, 836, 380, $ink);
        imagefilledrectangle($canvas, 640, 316, 760, 356, $ink);
        imagefilledrectangle($canvas, 724, 440, 856, 600, $solid);

        foreach ([[560, 250, 46], [500, 330, 34], [566, 410, 28], [470, 430, 22]] as [$x, $y, $size]) {
            imagefilledellipse($canvas, $x, $y, $size, $size, $solid);
        }
    }

    /**
     * A wordless brand mark: three bars in the accent colour. No text is
     * drawn anywhere on these covers - GD cannot shape Arabic, and a
     * broken word on an image is worse than no word at all.
     */
    private function brandMark(GdImage $canvas, string $accent): void
    {
        $bar = $this->allocate($canvas, $accent, 70);

        foreach ([[110, 300], [110, 190], [110, 110]] as $index => [$x, $length]) {
            $y = 700 + $index * 34;
            imagefilledrectangle($canvas, $x, $y, $x + $length, $y + 14, $bar);
        }
    }

    /**
     * @param  int  $opacity  0 (invisible) to 127 (solid)
     */
    private function allocate(GdImage $canvas, string $hex, int $opacity): int
    {
        [$r, $g, $b] = $this->rgb($hex);

        return (int) imagecolorallocatealpha($canvas, $r, $g, $b, 127 - max(0, min(127, $opacity)));
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
