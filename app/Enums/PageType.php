<?php

namespace App\Enums;

enum PageType: string
{
    case Service = 'service';
    case Area = 'area';
    case Project = 'project';
    case Article = 'article';
    case Offer = 'offer';
    case About = 'about';
    case Trust = 'trust';
    case Legal = 'legal';
    case Landing = 'landing';
}
