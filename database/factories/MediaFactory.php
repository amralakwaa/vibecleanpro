<?php

namespace Database\Factories;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * The default is a verified, privacy-cleared photo of the company's own
     * work - the only kind a published page may show.
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'media/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 500_000),
            'width' => 1200,
            'height' => 800,
            'alt_text' => fake()->sentence(4),
            'caption' => null,
            'variants' => null,
            'status' => MediaStatus::Ready,
            'privacy_status' => MediaPrivacyStatus::Cleared,
            'media_type' => MediaType::Real,
            'source' => 'vibe_library',
            'verified_description' => fake()->sentence(6),
        ];
    }

    /**
     * A test that blanks the alt text or description is describing a photo
     * that is not ready yet - keep the record consistent instead of making
     * every such test also override the status.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Media $media) {
            if ($media->status === MediaStatus::Ready && $media->readinessProblem()) {
                $media->status = MediaStatus::Pending;
            }
        });
    }

    public function pending(): static
    {
        return $this->state(['status' => MediaStatus::Pending, 'privacy_status' => MediaPrivacyStatus::Unverified]);
    }

    public function private(): static
    {
        return $this->state(['status' => MediaStatus::Private, 'privacy_status' => MediaPrivacyStatus::HeldWorkerConsent]);
    }

    public function placeholder(): static
    {
        return $this->state([
            'status' => MediaStatus::Pending,
            'media_type' => MediaType::Placeholder,
            'source' => 'placeholder',
            'alt_text' => null,
            'verified_description' => null,
        ]);
    }
}
