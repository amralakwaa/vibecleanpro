<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class MediaPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'media';
}
