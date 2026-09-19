<?php

namespace Tests\Feature;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class MediaVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_photo_cannot_be_ready_before_its_privacy_is_cleared(): void
    {
        $media = Media::factory()->pending()->create();

        $this->expectException(ValidationException::class);
        $media->update(['status' => MediaStatus::Ready]);
    }

    public function test_a_photo_cannot_be_ready_without_alt_text_or_a_verified_description(): void
    {
        foreach ([['alt_text' => null], ['verified_description' => null]] as $missing) {
            try {
                Media::factory()->create()->update($missing);
                $this->fail('A ready photo lost its '.array_key_first($missing).' and stayed ready.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('status', $exception->errors());
            }
        }
    }

    public function test_a_placeholder_can_never_be_ready(): void
    {
        $placeholder = Media::factory()->placeholder()->create(['alt_text' => 'نص', 'privacy_status' => MediaPrivacyStatus::Cleared]);

        $this->expectException(ValidationException::class);
        $placeholder->update(['status' => MediaStatus::Ready]);
    }

    public function test_a_held_photo_is_always_private_whatever_status_is_requested(): void
    {
        $media = Media::factory()->create(['privacy_status' => MediaPrivacyStatus::HeldWorkerConsent, 'status' => MediaStatus::Ready]);

        $this->assertSame(MediaStatus::Private, $media->fresh()->status);
    }

    public function test_the_default_factory_photo_is_verified_real_and_ready(): void
    {
        $media = Media::factory()->create();

        $this->assertSame(MediaStatus::Ready, $media->status);
        $this->assertTrue($media->media_type->isEvidence());
        $this->assertNull($media->readinessProblem());
    }

    public function test_the_library_import_assigns_status_from_the_catalogue_and_is_idempotent(): void
    {
        Storage::fake('public');
        [$package, $manifest] = $this->fixturePackage([
            ['file' => 'vibe-clean-pro-cleared.webp', 'privacy_status' => 'cleared', 'alt_text' => 'عامل ينظف', 'verified_description' => 'worker cleaning'],
            ['file' => 'vibe-clean-pro-cleared-no-alt.webp', 'privacy_status' => 'cleared', 'alt_text' => null, 'verified_description' => 'worker cleaning'],
            ['file' => 'vibe-clean-pro-held.webp', 'privacy_status' => 'held_worker_consent', 'alt_text' => null, 'verified_description' => 'workers visible'],
            ['file' => 'vibe-clean-pro-brand.webp', 'privacy_status' => 'held_third_party_brand', 'alt_text' => null, 'verified_description' => 'restaurant sign'],
            ['file' => 'vibe-clean-pro-unmapped.webp', 'privacy_status' => 'unverified', 'alt_text' => null, 'verified_description' => null],
        ]);

        $this->artisan('media:import-library', ['--package' => $package, '--manifest' => $manifest])->assertSuccessful();

        $status = fn (string $file) => Media::query()->where('original_filename', $file)->sole()->status;
        $this->assertSame(MediaStatus::Ready, $status('vibe-clean-pro-cleared.webp'));
        $this->assertSame(MediaStatus::Pending, $status('vibe-clean-pro-cleared-no-alt.webp'));
        $this->assertSame(MediaStatus::Private, $status('vibe-clean-pro-held.webp'));
        $this->assertSame(MediaStatus::Private, $status('vibe-clean-pro-brand.webp'));
        $this->assertSame(MediaStatus::Pending, $status('vibe-clean-pro-unmapped.webp'));
        Storage::disk('public')->assertExists('media/vibe/vibe-clean-pro-cleared.webp');
        $this->assertSame(MediaType::Real, Media::query()->first()->media_type);

        Media::query()->where('original_filename', 'vibe-clean-pro-cleared.webp')->update(['alt_text' => 'نص عدّله المحرر']);
        $this->artisan('media:import-library', ['--package' => $package, '--manifest' => $manifest])->assertSuccessful();

        $this->assertSame(5, Media::query()->count());
        $this->assertSame('نص عدّله المحرر', Media::query()->where('original_filename', 'vibe-clean-pro-cleared.webp')->value('alt_text'));
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        Storage::fake('public');
        [$package, $manifest] = $this->fixturePackage([
            ['file' => 'vibe-clean-pro-cleared.webp', 'privacy_status' => 'cleared', 'alt_text' => 'عامل', 'verified_description' => 'worker'],
        ]);

        $this->artisan('media:import-library', ['--package' => $package, '--manifest' => $manifest, '--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, Media::query()->count());
        Storage::disk('public')->assertMissing('media/vibe/vibe-clean-pro-cleared.webp');
    }

    /**
     * @param  list<array{file: string, privacy_status: string, alt_text: ?string, verified_description: ?string}>  $items
     * @return array{0: string, 1: string}
     */
    private function fixturePackage(array $items): array
    {
        $directory = storage_path('framework/testing/media-import-'.uniqid());
        mkdir($directory, 0777, true);

        $image = imagecreatetruecolor(4, 3);
        ob_start();
        imagewebp($image);
        $webp = (string) ob_get_clean();

        $zip = new ZipArchive;
        $zip->open($directory.'/package.zip', ZipArchive::CREATE);

        foreach ($items as $item) {
            $zip->addFromString($item['file'], $webp);
        }

        $zip->close();

        $manifest = ['items' => array_map(fn (array $item) => [...$item, 'original' => null, 'captured_stage' => 'during', 'source_group' => null], $items)];
        file_put_contents($directory.'/manifest.json', json_encode($manifest));

        return [$directory.'/package.zip', $directory.'/manifest.json'];
    }
}
