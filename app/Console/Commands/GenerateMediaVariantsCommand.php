<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Support\Media\ResponsiveImageGenerator;
use Illuminate\Console\Command;

class GenerateMediaVariantsCommand extends Command
{
    protected $signature = 'media:generate-variants {--missing : Only photos that have no variants yet}';

    protected $description = 'Write the responsive WebP sizes (srcset) for every photo in the library';

    public function handle(ResponsiveImageGenerator $generator): int
    {
        $query = Media::query()->where('mime_type', 'like', 'image/%')->when($this->option('missing'), fn ($query) => $query->whereNull('variants'));
        $count = 0;

        $this->withProgressBar($query->lazyById(50), function (Media $media) use ($generator, &$count) {
            $count += $generator->generate($media) !== [] ? 1 : 0;
        });

        $this->newLine();
        $this->info("Responsive sizes written for {$count} photo(s).");

        return self::SUCCESS;
    }
}
