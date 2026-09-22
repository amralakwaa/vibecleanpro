<?php

use App\Models\Area;
use App\Seo\PublishingGate;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$gate = app(PublishingGate::class);
$areas = ['al-olaya', 'al-malqa', 'al-narjis', 'al-yasmin', 'al-aqiq', 'diriyah', 'al-qirawan', 'hittin', 'al-yarmouk', 'al-rimal'];

function shingles(string $text)
{
    $normalized = mb_strtolower(trim($text));
    $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? '';
    $words = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $shingles = [];
    for ($i = 0; $i <= count($words) - 3; $i++) {
        $shingles[] = implode(' ', array_slice($words, $i, 3));
    }

    return array_values(array_unique($shingles));
}

function extractText($page)
{
    $parts = [$page->title];
    foreach ($page->contentBlocks as $block) {
        if (isset($block->data['content'])) {
            $parts[] = strip_tags($block->data['content']);
        }
    }

    return implode(' ', $parts);
}

// Pre-calculate shingles
$pageShingles = [];
$pages = [];
foreach ($areas as $slug) {
    $area = Area::where('slug', $slug)->first();
    if ($area && $area->page) {
        $pageShingles[$slug] = shingles(extractText($area->page));
        $pages[$slug] = $area;
    }
}

echo "Area | Gate Result | Warnings | Errors | Max Similarity | Index Eligibility\n";
echo "---|---|---|---|---|---\n";

foreach ($pages as $slug => $area) {
    // Set robots_index to true to test index eligibility
    $area->page->seoMetadata->robots_index = true;

    $result = $gate->evaluate($area->page);

    $warnings = collect($result->checks)->where('severity.value', 'warning')->count();
    $errors = collect($result->checks)->where('severity.value', 'error')->count();

    $maxSim = 0;
    foreach ($pages as $otherSlug => $otherArea) {
        if ($slug === $otherSlug) {
            continue;
        }

        $a = $pageShingles[$slug];
        $b = $pageShingles[$otherSlug];

        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique([...$a, ...$b]));
        $sim = $union > 0 ? $intersection / $union : 0.0;

        if ($sim > $maxSim) {
            $maxSim = $sim;
        }
    }

    $simPercent = round($maxSim * 100);
    $eligibility = $errors === 0 ? 'Yes' : 'No';
    $status = $errors === 0 ? 'PASS' : 'FAIL';

    $errorMsgs = collect($result->checks)->where('severity.value', 'error')->pluck('message')->implode('; ');

    echo "{$area->name} | {$status} | {$warnings} | {$errors} | {$simPercent}% | {$eligibility} | {$errorMsgs}\n";

    $area->page->seoMetadata->save();
}
