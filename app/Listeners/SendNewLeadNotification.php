<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\BusinessProfile;
use App\Notifications\NewLeadNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Emails the team about a new lead. Queued, so the visitor's request
 * only ever waits for the lead to be stored; delivery happens in the
 * worker. The recipient is the managed lead_notification_email on the
 * business profile - nothing is sent (and nothing is lost) while it is
 * empty.
 *
 * A delivery failure is deliberately NOT caught here: the exception must
 * leave handle() so the queue marks the attempt as failed, retries per
 * $tries/$backoff, and after the last attempt records the job in
 * failed_jobs and calls failed(). Swallowing it would make the worker
 * count a lost email as a success. The lead itself is never at risk -
 * it was stored before the event was dispatched.
 */
class SendNewLeadNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public function handle(LeadCreated $event): void
    {
        $recipient = BusinessProfile::query()->value('lead_notification_email');

        if (blank($recipient)) {
            Log::info('Lead stored but not emailed: no lead notification email is configured.', ['lead_id' => $event->lead->id]);

            return;
        }

        Notification::route('mail', $recipient)->notify(new NewLeadNotification($event->lead));
    }

    /**
     * Observability only: the lead is untouched (it is already stored and
     * visible in the panel) and nothing sensitive is written - the lead id
     * and the failure message, never credentials or the lead payload.
     */
    public function failed(LeadCreated $event, Throwable $exception): void
    {
        Log::error('New-lead email failed after all retries; the lead is stored and visible in the panel.', [
            'lead_id' => $event->lead->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
