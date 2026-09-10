<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class RedirectPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'redirect';
}
