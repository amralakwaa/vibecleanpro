<?php

namespace App\Console\Commands;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Console\Command;

/**
 * Data minimisation for leads (Saudi PDPL): technical identifiers are only
 * useful for spam triage in the first weeks, and spam itself is never
 * business data worth keeping.
 */
class PruneLeadPersonalData extends Command
{
    public const TECHNICAL_DATA_DAYS = 90;

    public const SPAM_DAYS = 30;

    protected $signature = 'leads:prune-personal-data';

    protected $description = 'Clear lead IP/user-agent after 90 days and permanently delete spam older than 30 days';

    public function handle(): int
    {
        $cleared = Lead::withTrashed()
            ->where('created_at', '<', now()->subDays(self::TECHNICAL_DATA_DAYS))
            ->where(fn ($query) => $query->whereNotNull('ip_address')->orWhereNotNull('user_agent'))
            ->update(['ip_address' => null, 'user_agent' => null]);

        $deleted = Lead::withTrashed()
            ->where('status', LeadStatus::Spam)
            ->where('created_at', '<', now()->subDays(self::SPAM_DAYS))
            ->forceDelete();

        $this->components->info("Cleared technical data on {$cleared} lead(s); permanently deleted {$deleted} spam lead(s).");

        return self::SUCCESS;
    }
}
