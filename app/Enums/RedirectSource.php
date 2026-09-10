<?php

namespace App\Enums;

enum RedirectSource: string
{
    case SlugChange = 'slug_change';
    case Manual = 'manual';
}
