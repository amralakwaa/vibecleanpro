<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Quoted = 'quoted';
    case Won = 'won';
    case Lost = 'lost';
    case Spam = 'spam';
}
