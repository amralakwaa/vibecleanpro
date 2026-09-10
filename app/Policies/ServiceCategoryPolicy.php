<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class ServiceCategoryPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'service_category';
}
