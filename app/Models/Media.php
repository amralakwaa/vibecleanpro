<?php

namespace App\Models;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Observers\MediaObserver;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(MediaObserver::class)]
#[Fillable([
    'disk', 'path', 'original_filename', 'source_original_name', 'mime_type', 'size', 'width', 'height', 'alt_text', 'caption', 'variants',
    'status', 'privacy_status', 'media_type', 'source', 'source_group', 'verified_description', 'captured_stage', 'captured_at',
    'consent_ref', 'content_hash', 'service_id', 'area_id',
])]
class Media extends Model
{
    public const LIBRARY_DIRECTORY = 'media/library';

    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'status' => MediaStatus::class,
            'privacy_status' => MediaPrivacyStatus::class,
            'media_type' => MediaType::class,
            'captured_at' => 'date',
        ];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * The responsive WebP sizes plus the original as the largest candidate,
     * or an empty string when none were generated (browsers then fall back
     * to src).
     */
    public function srcset(): string
    {
        if (empty($this->variants)) {
            return '';
        }

        $disk = Storage::disk($this->disk);
        $candidates = collect($this->variants)->map(fn (string $path, int|string $width) => $disk->url($path).' '.$width.'w');

        if ($this->width) {
            $candidates->push($this->url().' '.$this->width.'w');
        }

        return $candidates->implode(', ');
    }

    /**
     * Stock illustrations imported by InitialMediaSeeder live under one
     * directory. They are fine for the hero, services and generic visuals,
     * but must never be presented as the company's own project work - so
     * the project media picker filters them out.
     */
    public function isLibraryStock(): bool
    {
        return str_starts_with($this->path, self::LIBRARY_DIRECTORY.'/');
    }

    /**
     * @param  Builder<Media>  $query
     */
    public function scopeExcludingLibraryStock(Builder $query): void
    {
        $nonEvidence = collect(MediaType::cases())
            ->reject(fn (MediaType $type) => $type->isEvidence())
            ->map(fn (MediaType $type) => $type->value)
            ->all();

        $query->where('path', 'not like', self::LIBRARY_DIRECTORY.'/%')
            ->where(fn (Builder $inner) => $inner->whereNull('media_type')->orWhereNotIn('media_type', $nonEvidence));
    }

    /**
     * @param  Builder<Media>  $query
     */
    public function scopePublishable(Builder $query): void
    {
        $query->whereIn('status', MediaStatus::publishableValues());
    }

    /**
     * Why this file may not be marked ready, or null when it may.
     */
    public function readinessProblem(): ?string
    {
        return match (true) {
            $this->media_type === MediaType::Placeholder => 'الصورة المؤقتة (Placeholder) لا تكون جاهزة للنشر أبدًا.',
            $this->privacy_status !== MediaPrivacyStatus::Cleared => 'لا يمكن اعتماد صورة قبل مراجعة خصوصيتها (حالة الخصوصية يجب أن تكون: مُراجَع — لا مانع).',
            blank($this->alt_text) => 'أضف النص البديل (Alt) قبل اعتماد الصورة.',
            in_array($this->media_type, [MediaType::Real, MediaType::Illustration], true) && blank($this->verified_description) => 'أضف الوصف الموثّق لما تُظهره الصورة فعلًا قبل اعتمادها.',
            default => null,
        };
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_media')
            ->withPivot(['stage', 'sort_order'])
            ->withTimestamps();
    }
}
