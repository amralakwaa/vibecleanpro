<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;
use App\Policies\Concerns\AuthorizesSoftDeletableResource;

class ServicePolicy
{
    use AuthorizesResource, AuthorizesSoftDeletableResource;

    protected string $permissionPrefix = 'service';
}
