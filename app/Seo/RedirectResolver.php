<?php

namespace App\Seo;

use App\Models\Redirect;
use App\Seo\ValueObjects\RedirectResolution;

/**
 * Runtime lookup for "is there a redirect for this exact path". A single
 * indexed query, no recursion: the redirects table is kept chain-free by
 * construction (see the slug-change Action, which flattens on write), so a
 * one-hop lookup here is both correct and cheap - there is nothing to loop
 * over.
 */
class RedirectResolver
{
    public function resolve(string $path): ?RedirectResolution
    {
        $redirect = Redirect::query()
            ->where('from_path', $this->normalize($path))
            ->where('is_active', true)
            ->first();

        if (! $redirect) {
            return null;
        }

        return new RedirectResolution(
            redirectId: $redirect->id,
            to: $redirect->to_path,
            status: $redirect->type,
        );
    }

    public function recordHit(int $redirectId): void
    {
        Redirect::query()->whereKey($redirectId)->increment('hits');
    }

    private function normalize(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
