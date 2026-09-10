<?php

namespace App\Enums;

enum MediaStage: string
{
    case Before = 'before';
    case During = 'during';
    case After = 'after';
}
