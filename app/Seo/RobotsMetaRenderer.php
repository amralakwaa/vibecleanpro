<?php

namespace App\Seo;

use App\Seo\ValueObjects\IndexabilityDecision;

/**
 * Turns an IndexabilityDecision into the literal <meta name="robots">
 * content string. Deliberately small: only index/noindex + follow/nofollow
 * are supported, because that is the only pair this project actually needs.
 *
 * We never use robots.txt to hide a page we want deindexed - a disallowed
 * page can't be crawled, so Google never sees the noindex tag and may keep
 * the URL indexed from other signals. noindex only works if the page stays
 * crawlable.
 */
class RobotsMetaRenderer
{
    public function render(IndexabilityDecision $decision): string
    {
        $index = $decision->indexable ? 'index' : 'noindex';
        $follow = $decision->follow ? 'follow' : 'nofollow';

        return "{$index}, {$follow}";
    }
}
