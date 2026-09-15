<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class TeamMemberPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'team_member';
}
