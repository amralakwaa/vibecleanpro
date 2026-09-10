<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;
use App\Policies\Concerns\AuthorizesSoftDeletableResource;

class LeadPolicy
{
    use AuthorizesResource, AuthorizesSoftDeletableResource;

    protected string $permissionPrefix = 'lead';
}
