<?php

namespace App\Support\Launch;

use InvalidArgumentException;

/**
 * The owner-approved launch decisions (database/seeders/content/
 * launch-manifest.json). Loading fails loudly on a missing file or a
 * malformed shape, so nothing is ever applied from a half-read manifest.
 */
final readonly class LaunchManifest
{
    /**
     * @param  list<string>  $confirmProjectRefs
     * @param  array<string, list<string>>  $articleServices
     * @param  list<string>  $publishServices
     * @param  list<string>  $publishArticles
     * @param  list<string>  $publishProjectRefs
     */
    public function __construct(
        public array $confirmProjectRefs,
        public array $articleServices,
        public array $publishServices,
        public array $publishArticles,
        public array $publishProjectRefs,
        public ?string $homepageHeroMediaFile,
    ) {}

    public static function defaultPath(): string
    {
        return database_path('seeders/content/launch-manifest.json');
    }

    public static function load(?string $path = null): self
    {
        $path ??= self::defaultPath();

        if (! is_file($path)) {
            throw new InvalidArgumentException("Launch manifest not found: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ($data['version'] ?? null) !== 1) {
            throw new InvalidArgumentException('Launch manifest is not valid JSON with "version": 1.');
        }

        $list = function (mixed $value, string $key): array {
            if (! is_array($value) || ! array_is_list($value) || array_filter($value, fn ($item) => ! is_string($item) || $item === '') !== []) {
                throw new InvalidArgumentException("Launch manifest key \"{$key}\" must be a list of non-empty strings.");
            }

            if (count($value) !== count(array_unique($value))) {
                throw new InvalidArgumentException("Launch manifest key \"{$key}\" contains duplicates.");
            }

            return $value;
        };

        $articleServices = $data['article_services'] ?? [];

        if (! is_array($articleServices) || array_is_list($articleServices) && $articleServices !== []) {
            throw new InvalidArgumentException('Launch manifest key "article_services" must map article slugs to service slugs.');
        }

        $hero = $data['homepage_hero_media_file'] ?? null;

        if ($hero !== null && (! is_string($hero) || $hero === '')) {
            throw new InvalidArgumentException('Launch manifest key "homepage_hero_media_file" must be a file name or null.');
        }

        return new self(
            confirmProjectRefs: $list($data['confirm_projects']['source_refs'] ?? [], 'confirm_projects.source_refs'),
            articleServices: collect($articleServices)->map(fn ($services, $article) => $list($services, "article_services.{$article}"))->all(),
            publishServices: $list($data['publish']['services'] ?? [], 'publish.services'),
            publishArticles: $list($data['publish']['articles'] ?? [], 'publish.articles'),
            publishProjectRefs: $list($data['publish']['projects'] ?? [], 'publish.projects'),
            homepageHeroMediaFile: $hero,
        );
    }
}
