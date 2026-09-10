<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class AreaGroupPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'area_group';
}
