<?php

namespace App\Support\Launch;

use App\Console\Commands\ImportMediaLibrary;
use App\Enums\MediaStatus;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Article;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Seo\PublishingGate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns the launch manifest into a plan, and applies it.
 *
 * plan() only reads: it resolves every item in the manifest, reports
 * anything missing, and runs the Publishing Gate against each page to be
 * published as it WILL be (a project listed for confirmation is evaluated
 * as confirmed, in memory only). It also refuses a page whose content
 * links to a site page that is neither published nor part of this launch.
 *
 * apply() re-plans inside one transaction and writes nothing if the plan
 * has any problem. Every write is conditional (confirm only unconfirmed,
 * publish only unpublished, attach only missing relations), so running it
 * again changes nothing. If the Gate still reverts a page on save, the
 * whole transaction rolls back.
 */
class LaunchPlanner
{
    /**
     * URL prefix => page type, as UrlResolver builds them.
     */
    private const PREFIXES = [
        'services' => PageType::Service,
        'areas' => PageType::Area,
        'projects' => PageType::Project,
        'blog' => PageType::Article,
        'offers' => PageType::Offer,
    ];

    public function __construct(private readonly PublishingGate $gate) {}

    public function plan(LaunchManifest $manifest): LaunchPlan
    {
        $plan = new LaunchPlan;

        $projects = $this->planConfirmations($manifest, $plan);
        $this->planArticleServices($manifest, $plan);
        $this->planHero($manifest, $plan);

        $pages = $this->pagesToPublish($manifest, $projects, $plan);
        $plannedIds = $pages->pluck('id')->all();

        foreach ($pages as $page) {
            $this->planPublish($page, $manifest, $plannedIds, $plan);
        }

        return $plan;
    }

    public function apply(LaunchManifest $manifest): LaunchPlan
    {
        return DB::transaction(function () use ($manifest): LaunchPlan {
            $plan = $this->plan($manifest);

            if ($plan->hasProblems()) {
                throw new LaunchAbortedException($plan);
            }

            Project::query()->whereIn('source_ref', $manifest->confirmProjectRefs)->whereNull('owner_confirmed_at')->get()
                ->each(fn (Project $project) => $project->update(['owner_confirmed_at' => now()]));

            foreach ($manifest->articleServices as $articleSlug => $serviceSlugs) {
                $article = $this->pageOfType($articleSlug, PageType::Article)?->pageable;
                $serviceIds = $this->servicePages($serviceSlugs)->map(fn (Page $page) => $page->pageable_id)->all();
                $article?->services()->syncWithoutDetaching($serviceIds);
            }

            if ($manifest->homepageHeroMediaFile !== null) {
                SiteSetting::query()->updateOrCreate(
                    ['key' => SiteSetting::HOME_HERO_MEDIA_ID],
                    ['value' => (string) $this->libraryMedia($manifest->homepageHeroMediaFile)?->id, 'type' => 'int'],
                );
            }

            foreach ($this->pagesToPublish($manifest, $this->confirmableProjects($manifest), new LaunchPlan) as $page) {
                if ($page->status === PageStatus::Published) {
                    continue;
                }

                $page->update(['status' => PageStatus::Published, 'published_at' => $page->published_at ?? now()]);

                if ($page->refresh()->status !== PageStatus::Published) {
                    $failed = new LaunchPlan;
                    $failed->problem("Publishing Gate reverted {$page->slug} to draft on save.");

                    throw new LaunchAbortedException($failed);
                }
            }

            return $plan;
        });
    }

    /**
     * @return Collection<string, Project>
     */
    private function planConfirmations(LaunchManifest $manifest, LaunchPlan $plan): Collection
    {
        $projects = $this->confirmableProjects($manifest);

        foreach ($manifest->confirmProjectRefs as $ref) {
            $project = $projects->get($ref);

            if (! $project) {
                $plan->problem("Project {$ref} is not in the database (run ProductionContentSeeder first).");

                continue;
            }

            if ($project->trashed()) {
                $plan->problem("Project {$ref} is in the trash; restore it or remove it from the manifest.");

                continue;
            }

            $plan->step('confirm project', $ref, $project->owner_confirmed_at ? 'already confirmed' : 'confirm', $project->owner_confirmed_at === null);
        }

        return $projects;
    }

    private function planArticleServices(LaunchManifest $manifest, LaunchPlan $plan): void
    {
        foreach ($manifest->articleServices as $articleSlug => $serviceSlugs) {
            $article = $this->pageOfType($articleSlug, PageType::Article)?->pageable;

            if (! $article instanceof Article) {
                $plan->problem("Article {$articleSlug} is not in the database (run ArticleContentSeeder first).");

                continue;
            }

            $servicePages = $this->servicePages($serviceSlugs);

            foreach (array_diff($serviceSlugs, $servicePages->pluck('slug')->all()) as $missing) {
                $plan->problem("Service {$missing} (linked from article {$articleSlug}) is not in the database.");
            }

            $missingIds = array_diff($servicePages->pluck('pageable_id')->all(), $article->services()->pluck('services.id')->all());
            $plan->step('article → services', $articleSlug, $missingIds === [] ? 'already linked' : 'link '.count($missingIds).' service(s)', $missingIds !== []);
        }
    }

    private function planHero(LaunchManifest $manifest, LaunchPlan $plan): void
    {
        if ($manifest->homepageHeroMediaFile === null) {
            return;
        }

        $media = $this->libraryMedia($manifest->homepageHeroMediaFile);

        if (! $media) {
            $plan->problem("Homepage hero {$manifest->homepageHeroMediaFile} is not in the media library.");

            return;
        }

        if ($media->status !== MediaStatus::Ready) {
            $plan->problem("Homepage hero {$manifest->homepageHeroMediaFile} is not approved (status: {$media->status->value}).");

            return;
        }

        $current = (int) SiteSetting::get(SiteSetting::HOME_HERO_MEDIA_ID);
        $plan->step('homepage hero', $manifest->homepageHeroMediaFile, $current === $media->id ? 'already set' : 'set', $current !== $media->id);
    }

    /**
     * @param  Collection<string, Project>  $projects
     * @return Collection<int, Page>
     */
    private function pagesToPublish(LaunchManifest $manifest, Collection $projects, LaunchPlan $plan): Collection
    {
        $pages = collect();

        foreach ([[PageType::Service, $manifest->publishServices], [PageType::Article, $manifest->publishArticles]] as [$type, $slugs]) {
            foreach ($slugs as $slug) {
                $page = $this->pageOfType($slug, $type);
                $page ? $pages->push($page) : $plan->problem("{$type->value} page {$slug} is not in the database.");
            }
        }

        // Standalone CMS pages (about / trust / legal / landing) are matched
        // by slug alone, because their type is theirs to choose.
        foreach ($manifest->publishPages as $slug) {
            $page = Page::query()->where('slug', $slug)
                ->whereIn('type', [PageType::About, PageType::Trust, PageType::Legal, PageType::Landing])
                ->first();
            $page ? $pages->push($page) : $plan->problem("Standalone page {$slug} is not in the database.");
        }

        foreach ($manifest->publishProjectRefs as $ref) {
            if (! in_array($ref, $manifest->confirmProjectRefs, true)) {
                $plan->problem("Project {$ref} is listed for publishing but not for owner confirmation.");

                continue;
            }

            $page = $projects->get($ref)?->page;
            $page ? $pages->push($page) : $plan->problem("Project {$ref} has no page to publish.");
        }

        return $pages;
    }

    /**
     * @param  list<int>  $plannedIds
     */
    private function planPublish(Page $page, LaunchManifest $manifest, array $plannedIds, LaunchPlan $plan): void
    {
        // Evaluate the page as it will be after this launch: a project in
        // the confirmation list counts as confirmed (in memory only).
        if ($page->pageable instanceof Project && in_array($page->pageable->source_ref, $manifest->confirmProjectRefs, true)) {
            $page->pageable->owner_confirmed_at ??= now();
        }

        $errors = $this->gate->evaluate($page)->errors();

        foreach ($errors as $error) {
            $plan->problem("Publishing Gate refuses {$page->type->value} {$page->slug}: {$error->message}");
        }

        foreach ($this->unpublishedLinkTargets($page, $plannedIds) as $path) {
            $plan->problem("{$page->type->value} {$page->slug} links to {$path}, which is neither published nor part of this launch.");
        }

        $alreadyPublished = $page->status === PageStatus::Published;
        $plan->step('publish '.$page->type->value, $page->slug, $alreadyPublished ? 'already published' : 'publish', ! $alreadyPublished);
    }

    /**
     * Site paths linked from the page's active blocks whose page exists
     * but will not be live after this launch.
     *
     * @param  list<int>  $plannedIds
     * @return list<string>
     */
    private function unpublishedLinkTargets(Page $page, array $plannedIds): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $targets = [];

        foreach ($page->contentBlocks()->where('is_active', true)->get() as $block) {
            $urls = [];

            if (is_string($block->data['content'] ?? null)) {
                preg_match_all('/href="([^"]+)"/', $block->data['content'], $matches);
                $urls = $matches[1];
            }

            if (is_string($block->data['button_url'] ?? null)) {
                $urls[] = $block->data['button_url'];
            }

            foreach ($urls as $url) {
                $host = parse_url($url, PHP_URL_HOST);

                if ($host !== null && $host !== $appHost) {
                    continue;
                }

                $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
                $segments = $path === '' ? [] : explode('/', $path);
                $target = match (true) {
                    count($segments) === 2 && isset(self::PREFIXES[$segments[0]]) => $this->pageOfType($segments[1], self::PREFIXES[$segments[0]]),
                    count($segments) === 1 => Page::query()->where('slug', $segments[0])->whereIn('type', [PageType::About, PageType::Trust, PageType::Legal, PageType::Landing])->first(),
                    default => null,
                };

                if ($target && $target->status !== PageStatus::Published && ! in_array($target->id, $plannedIds, true)) {
                    $targets[] = '/'.$path;
                }
            }
        }

        return array_values(array_unique($targets));
    }

    /**
     * @return Collection<string, Project>
     */
    private function confirmableProjects(LaunchManifest $manifest): Collection
    {
        return Project::query()->withTrashed()
            ->whereIn('source_ref', array_unique([...$manifest->confirmProjectRefs, ...$manifest->publishProjectRefs]))
            ->with('page')
            ->get()
            ->keyBy('source_ref');
    }

    private function pageOfType(string $slug, PageType $type): ?Page
    {
        return Page::query()->where('slug', $slug)->where('type', $type)->first();
    }

    /**
     * @param  list<string>  $slugs
     * @return Collection<int, Page>
     */
    private function servicePages(array $slugs): Collection
    {
        return Page::query()->where('type', PageType::Service)->whereIn('slug', $slugs)
            ->whereHasMorph('pageable', [Service::class])
            ->get();
    }

    private function libraryMedia(string $file): ?Media
    {
        return Media::query()->where('path', ImportMediaLibrary::DIRECTORY.'/'.$file)->first();
    }
}
