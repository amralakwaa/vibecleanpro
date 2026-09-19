<?php

namespace Tests\Feature;

use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use App\Support\Media\ResponsiveImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResponsiveImagesTest extends TestCase
{
    use RefreshDatabase;

    private function upload(int $width, int $height): Media
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('facade.jpg', $width, $height)->store('media', 'public');

        $media = Media::factory()->create(['path' => $path, 'disk' => 'public', 'mime_type' => 'image/jpeg', 'width' => $width, 'height' => $height]);

        Bus::assertDispatched(GenerateMediaVariants::class, fn (GenerateMediaVariants $job) => $job->media->is($media));
        (new GenerateMediaVariants($media))->handle(app(ResponsiveImageGenerator::class));

        return $media;
    }

    public function test_an_upload_gets_webp_sizes_smaller_than_the_original(): void
    {
        $media = $this->upload(1200, 800)->fresh();

        $this->assertSame([480, 960], array_keys($media->variants));
        foreach ($media->variants as $width => $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringEndsWith("-{$width}w.webp", $path);
            $this->assertSame($width, getimagesizefromstring(Storage::disk('public')->get($path))[0]);
        }

        $this->assertStringContainsString('480w', $media->srcset());
        $this->assertStringContainsString('1200w', $media->srcset());
    }

    public function test_a_small_image_has_no_variants_and_an_empty_srcset(): void
    {
        $media = $this->upload(400, 300)->fresh();

        $this->assertNull($media->variants);
        $this->assertSame('', $media->srcset());
    }

    public function test_deleting_a_photo_removes_its_variants(): void
    {
        $media = $this->upload(1000, 700)->fresh();
        $paths = array_values($media->variants);

        $media->delete();

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_the_backfill_command_fills_missing_variants(): void
    {
        $media = $this->upload(1000, 700);
        $media->forceFill(['variants' => null])->saveQuietly();

        $this->artisan('media:generate-variants', ['--missing' => true])->assertSuccessful();

        $this->assertSame([480, 960], array_keys($media->fresh()->variants));
    }
}
