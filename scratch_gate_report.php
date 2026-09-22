<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Area;
use App\Seo\PublishingGate;
use Illuminate\Contracts\Console\Kernel;

$slugs = ['al-olaya', 'al-malqa', 'al-narjis', 'al-yasmin', 'al-aqiq', 'diriyah', 'al-qirawan', 'hittin', 'al-yarmouk', 'al-rimal'];

$gate = app(PublishingGate::class);

foreach ($slugs as $slug) {
    $area = Area::where('slug', $slug)->first();
    if (! $area || ! $area->page) {
        echo "=== {$slug}: NO PAGE FOUND ===\n";

        continue;
    }

    $page = $area->page->load(['contentBlocks', 'seoMetadata', 'pageable.services', 'pageable.projects']);
    $result = $gate->evaluate($page);

    $errors = collect($result->checks)->where('severity.value', 'error');
    $warnings = collect($result->checks)->where('severity.value', 'warning');
    $blockCount = $page->contentBlocks->count();
    $similarity = collect($result->checks)->firstWhere('key', 'local_similarity');
    $robots = $page->seoMetadata?->robots_index ? 'index' : 'noindex';
    $seoTitle = $page->seoMetadata?->meta_title ?? '—';

    echo "=== {$slug} ({$area->name}) ===\n";
    echo "  Status: {$page->status->value} | Tier: {$area->tier->value} | Robots: {$robots}\n";
    echo "  Blocks: {$blockCount} | Services: {$area->services()->count()} | Projects: {$area->projects()->count()}\n";
    echo "  SEO Title: {$seoTitle}\n";
    echo '  Gate: '.($result->canPublish() ? 'CAN PUBLISH' : 'BLOCKED')."\n";
    if ($errors->isNotEmpty()) {
        echo '  ERRORS: '.$errors->pluck('key')->implode(', ')."\n";
    }
    if ($warnings->isNotEmpty()) {
        echo '  WARNINGS: '.$warnings->pluck('key')->implode(', ')."\n";
    }
    if ($similarity) {
        echo '  Similarity: '.$similarity->severity->value.' — '.$similarity->message."\n";
    }
    echo "\n";
}
