<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class InternalLinkPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'internal_link';
}
