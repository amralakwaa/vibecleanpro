<?php

use App\Models\Area;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$areas = Area::with('page')->orderBy('slug')->get();
foreach ($areas as $a) {
    echo $a->slug.'|'.($a->tier?->value ?? 'null').'|'.($a->page?->status?->value ?? 'no-page')."\n";
}
