<?php

namespace App\Jobs;

use App\Models\Media;
use App\Support\Media\ResponsiveImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMediaVariants implements ShouldQueue
{
    use Queueable;

    public function __construct(public Media $media) {}

    public function handle(ResponsiveImageGenerator $generator): void
    {
        $generator->generate($this->media);
    }
}
