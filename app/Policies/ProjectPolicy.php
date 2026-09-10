<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;
use App\Policies\Concerns\AuthorizesSoftDeletableResource;

class ProjectPolicy
{
    use AuthorizesResource, AuthorizesSoftDeletableResource;

    protected string $permissionPrefix = 'project';
}
