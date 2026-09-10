<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class FaqPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'faq';
}
