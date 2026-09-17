<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised by the public forms once a lead row is safely stored. Creating
 * the lead and telling the team about it are separate concerns: the
 * event is what lets delivery (email today, other channels later) fail,
 * retry or be switched off without the lead ever being at risk. It is
 * dispatched only by the public forms - a lead entered by hand in the
 * panel is already known to the team.
 */
class LeadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Lead $lead) {}
}
