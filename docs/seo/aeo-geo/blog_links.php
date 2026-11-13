<?php
// Usage: php blog_links.php [--apply]   (dry run without --apply)
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Page;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);
// source article => target article (topically adjacent, target has 0-1 inbound article links)
$pairs = [
    'disinfection-when-needed-riyadh' => 'ac-cleaning-when-why-riyadh',
    'annual-cleaning-contracts-riyadh' => 'ac-cleaning-when-why-riyadh',
    'facade-access-rope-lift-scaffold' => 'streak-free-glass-cleaning-riyadh',
    'villa-cleaning-checklist' => 'streak-free-glass-cleaning-riyadh',
    'palace-large-property-cleaning-riyadh' => 'courtyard-interlock-cleaning',
    'specialized-team-vs-domestic-workers-riyadh' => 'hourly-cleaning-riyadh',
    'apartment-move-in-out-cleaning-riyadh' => 'post-construction-handover-checklist',
    'sofa-majlis-cleaning-cost-riyadh' => 'sofa-majlis-cleaning-steam-or-wash-riyadh',
    'water-tank-cleaning-how-often-riyadh' => 'water-tank-cleaning-guide-riyadh',
];

$plan = [];
foreach ($pairs as $from => $to) {
    $src = Page::where('type', 'article')->where('slug', $from)->published()->with('contentBlocks')->firstOrFail();
    $dst = Page::where('type', 'article')->where('slug', $to)->published()->firstOrFail();
    $href = '/blog/'.$to;
    $block = $src->contentBlocks->where('is_active', true)->where('type', 'rich_text')->sortByDesc('position')->first();
    if (! $block) {
        throw new RuntimeException("$from: no rich_text block");
    }
    $all = $src->contentBlocks->where('is_active', true)->map(fn ($b) => json_encode($b->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->implode(' ');
    if (str_contains($all, $href.'"') || str_contains($all, 'vibecleanpro.com'.$href)) {
        echo "skip $from -> $to (already linked)\n";
        continue;
    }
    $p = '<p>للتوسع: <a href="'.$href.'">'.e($dst->title).'</a>.</p>';
    $plan[] = [$block, $p, $src];
    echo "$from [block {$block->id}] += $p\n";
}
if (! $apply) {
    echo "DRY RUN: ".count($plan)." links\n";
    exit;
}
file_put_contents(getenv('HOME').'/backup_bloglinks_'.date('Ymd_His').'.json', json_encode(array_map(fn ($x) => ['id' => $x[0]->id, 'data' => $x[0]->data], $plan), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
DB::transaction(function () use ($plan) {
    foreach ($plan as [$b, $p, $src]) {
        $d = $b->data;
        $d['content'] = rtrim($d['content'])."\n".$p;
        $b->update(['data' => $d]);
        $src->touch();
    }
});
echo 'applied: '.count($plan)."\n";
