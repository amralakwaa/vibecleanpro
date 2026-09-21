<?php

namespace App\Models;

use App\Enums\LocationConfidence;
use App\Enums\LocationEvidenceType;
use App\Enums\LocationSource;
use App\Enums\LocationStatus;
use App\Enums\MediaStage;
use App\Models\Concerns\HasPage;
use App\Observers\ProjectObserver;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['source_ref', 'area_id', 'title', 'summary', 'challenge', 'site_condition', 'execution_steps', 'outcome', 'client_problem', 'client_benefit', 'execution_difference', 'focus_keyword', 'cluster', 'city', 'neighborhood', 'landmark', 'location_note', 'location_source', 'location_evidence_type', 'location_confidence', 'location_evidence_reference', 'location_status', 'verified_at', 'verified_by', 'completed_at', 'owner_confirmed_at', 'is_featured', 'sort_order'])]
#[ObservedBy(ProjectObserver::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasPage, SoftDeletes;

    /**
     * Transient property to pass actor context to observers for history tracking.
     */
    public ?int $location_changed_by = null;

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'owner_confirmed_at' => 'datetime',
            'is_featured' => 'boolean',
            'location_source' => LocationSource::class,
            'location_evidence_type' => LocationEvidenceType::class,
            'location_confidence' => LocationConfidence::class,
            'location_status' => LocationStatus::class,
            'verified_at' => 'datetime',
            'execution_steps' => 'array',
        ];
    }

    /**
     * Whether this project has a written case study beyond its summary.
     * Each section renders only when it holds something real.
     */
    public function hasCaseStudy(): bool
    {
        return filled($this->challenge)
            || filled($this->site_condition)
            || filled($this->outcome)
            || filled($this->execution_steps);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'project_service')->withTimestamps();
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'project_media')
            ->withPivot(['stage', 'sort_order'])
            ->withTimestamps();
    }

    public function mediaByStage(MediaStage $stage): BelongsToMany
    {
        return $this->media()->wherePivot('stage', $stage->value)->orderByPivot('sort_order');
    }

    /**
     * Same data as media(), exposed as HasMany so Filament's Repeater can
     * manage it (Repeater's relationship() integration needs HasMany/
     * MorphMany, not BelongsToMany - see ProjectMedia).
     */
    public function projectMedia(): HasMany
    {
        return $this->hasMany(ProjectMedia::class)->orderBy('sort_order');
    }

    public function locationHistory(): HasMany
    {
        return $this->hasMany(ProjectLocationHistory::class)->latest('created_at');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * The place fields as one array - what a history row snapshots and
     * what the observer compares to detect a change.
     *
     * @return array<string, mixed>
     */
    public function locationSnapshot(): array
    {
        return [
            'area_id' => $this->area_id,
            'city' => $this->city,
            'neighborhood' => $this->neighborhood,
            'landmark' => $this->landmark,
            'location_note' => $this->location_note,
            'location_source' => $this->location_source?->value,
            'location_evidence_type' => $this->location_evidence_type?->value,
            'location_confidence' => $this->location_confidence?->value,
            'location_status' => $this->location_status?->value,
            'location_evidence_reference' => $this->location_evidence_reference,
        ];
    }

    /**
     * May this project's location power SEO? Every condition must hold:
     * status Verified, confidence at least MediaEvidence (3), an evidence
     * type AND a reference to the artefact, and a place actually set.
     * Miss any one and the district stays invisible to search - the gate
     * wants a decision backed by a document, not a decision alone.
     */
    public function hasVerifiedLocation(): bool
    {
        return $this->location_status === LocationStatus::Verified
            && $this->location_confidence?->meetsSeoThreshold() === true
            && $this->location_evidence_type !== null
            && filled($this->location_evidence_reference)
            && ($this->area_id || filled($this->neighborhood) || filled($this->city) || filled($this->landmark));
    }
}
