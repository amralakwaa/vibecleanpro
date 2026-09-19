<?php

namespace App\Support\Launch;

use RuntimeException;

class LaunchAbortedException extends RuntimeException
{
    public function __construct(public readonly LaunchPlan $plan)
    {
        parent::__construct('Launch aborted: '.implode(' | ', $plan->problems));
    }
}
