<?php

use App\Models\Area;
use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$areas = Area::pluck('slug', 'id')->toArray();
$projects = Project::with('media')->whereNull('location_status')->orWhere('location_status', 'draft')->get();
$found = [];

foreach ($projects as $p) {
    $matched = false;
    foreach ($areas as $id => $a) {
        if (mb_stripos($p->title, $a) !== false || mb_stripos($p->summary, $a) !== false || mb_stripos($p->source_ref, $a) !== false) {
            $found[] = ['pid' => $p->id, 'area' => $a, 'in' => 'text'];
            $matched = true;
        }
    }

    foreach ($p->media as $m) {
        foreach ($areas as $id => $a) {
            if (mb_stripos($m->original_filename, $a) !== false || mb_stripos($m->alt_text, $a) !== false || mb_stripos($m->verified_description, $a) !== false) {
                $found[] = ['pid' => $p->id, 'area' => $a, 'in' => 'media', 'file' => $m->original_filename];
                $matched = true;
            }
        }
    }
}
echo json_encode($found, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
