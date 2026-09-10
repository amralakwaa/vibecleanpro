<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesResource;

class ArticleCategoryPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'article_category';
}
