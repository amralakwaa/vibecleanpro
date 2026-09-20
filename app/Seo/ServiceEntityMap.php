<?php

namespace App\Seo;

/**
 * Reads the Service Entity Map (database/seeders/content/service-entity-map.php).
 *
 * The map is SEO organisation, not content: it says which query each
 * service page owns, what it may support, the intent behind it, and the
 * pages that belong around it. Nothing renders from here - it exists so
 * the ownership is written down once and can be checked, instead of
 * living in someone's head while two pages quietly chase one keyword.
 */
class ServiceEntityMap
{
    /**
     * @var array<string, array{primary: string, secondary: list<string>, intent: string, related: list<string>, articles: list<string>}>|null
     */
    private ?array $map = null;

    /**
     * @return array<string, array{primary: string, secondary: list<string>, intent: string, related: list<string>, articles: list<string>}>
     */
    public function all(): array
    {
        return $this->map ??= require database_path('seeders/content/service-entity-map.php');
    }

    /**
     * @return array{primary: string, secondary: list<string>, intent: string, related: list<string>, articles: list<string>}|null
     */
    public function for(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * Every query the catalogue claims to own, primary and secondary, as
     * phrase => service slug.
     *
     * @return array<string, string>
     */
    public function ownership(): array
    {
        $owners = [];

        foreach ($this->all() as $slug => $entry) {
            foreach ([$entry['primary'], ...$entry['secondary']] as $phrase) {
                $owners[$phrase] = $slug;
            }
        }

        return $owners;
    }

    /**
     * Phrases claimed by more than one service, as phrase => list of slugs.
     * An empty result is the anti-cannibalisation contract holding.
     *
     * @return array<string, list<string>>
     */
    public function collisions(): array
    {
        $claims = [];

        foreach ($this->all() as $slug => $entry) {
            foreach ([$entry['primary'], ...$entry['secondary']] as $phrase) {
                $claims[$phrase][] = $slug;
            }
        }

        return array_filter($claims, fn (array $slugs): bool => count($slugs) > 1);
    }
}
