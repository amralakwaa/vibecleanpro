<?php

namespace App\Models;

use App\Enums\LeadLostReason;
use App\Enums\LeadStatus;
use App\Observers\LeadObserver;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(LeadObserver::class)]
#[Fillable([
    'source_page_id', 'landing_page', 'source', 'attribution_code', 'service_id', 'area_id', 'project_id',
    'name', 'phone', 'email', 'message', 'notes', 'status', 'assigned_to', 'lost_reason',
    'ip_address', 'user_agent', 'device_type', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
    'contacted_at', 'quoted_at', 'completed_at', 'review_requested_at', 'consent_marketing',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'lost_reason' => LeadLostReason::class,
            'contacted_at' => 'datetime',
            'quoted_at' => 'datetime',
            'completed_at' => 'datetime',
            'review_requested_at' => 'datetime',
            'consent_marketing' => 'boolean',
        ];
    }

    public function sourcePage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'source_page_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
