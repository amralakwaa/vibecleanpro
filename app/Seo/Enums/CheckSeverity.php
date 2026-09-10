<?php

namespace App\Seo\Enums;

enum CheckSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Pass = 'pass';
}
