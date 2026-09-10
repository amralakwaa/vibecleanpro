<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['action', 'auditable_type', 'auditable_id', 'changes', 'ip_address'])]
class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * auditable_type/auditable_id are deliberately plain strings, not a real
     * morphTo(): audit logs must keep referencing every model in the app,
     * including ones outside the enforced morph map in AppServiceProvider,
     * so they store the fully-qualified class name directly instead.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
