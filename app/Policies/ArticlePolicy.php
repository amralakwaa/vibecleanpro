<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;
use App\Policies\Concerns\AuthorizesSoftDeletableResource;

class ArticlePolicy
{
    use AuthorizesResource, AuthorizesSoftDeletableResource;

    protected string $permissionPrefix = 'article';
}
