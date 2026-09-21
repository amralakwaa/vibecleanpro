<?php

namespace Database\Seeders;

use App\Enums\ProjectCluster;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMetadata;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns the 47 library project groups into case studies.
 *
 * Content comes from database/seeders/content/project-case-studies*.php,
 * grouped by source_ref. Two rules govern everything here:
 *
 *  - Every field is a DEFAULT. A column is written only while it is still
 *    empty, so an editor's work in Filament is never overwritten and this
 *    seeder stays safe to re-run.
 *  - This seeder publishes nothing. Publishing is site:launch + the
 *    manifest + the Publishing Gate, exactly as for services.
 *
 * Indexability is the one judgement it applies, and it is a readiness
 * test rather than a word count: see isIndexable(). Projects with no
 * cleared photograph at all get no case-study entry and stay drafts -
 * a proof page with nothing to show proves nothing.
 */
class ProjectCaseStudySeeder extends Seeder
{
    /** @var array<string, list<array{0: string, 1: string}>> */
    private array $faqBank = [];

    /** @var array<string, array<string, string>> */
    private array $captions = [];

    public function run(): void
    {
        $services = Service::query()
            ->with('page')
            ->get()
            ->mapWithKeys(fn (Service $service) => [
                Page::query()->where('type', 'service')->where('pageable_id', $service->id)->value('slug') => $service->id,
            ])
            ->filter(fn ($id, $slug) => filled($slug));

        $written = 0;
        $dated = 0;
        $linked = 0;
        $faqs = 0;
        $captions = 0;
        $this->faqBank = require database_path('seeders/content/project-case-study-faqs.php');
        $clientValue = require database_path('seeders/content/project-client-value.php');
        $this->captions = require database_path('seeders/content/project-captions.php');

        foreach (glob(database_path('seeders/content/project-case-studies*.php')) as $file) {
            foreach (require $file as $sourceRef => $definition) {
                $project = Project::query()->where('source_ref', $sourceRef)->first();

                if (! $project) {
                    $this->command?->warn("  {$sourceRef}: no project with this source_ref - skipped.");

                    continue;
                }

                $value = $clientValue[$sourceRef] ?? [];
                $definition['client_problem'] ??= $value['problem'] ?? null;
                $definition['client_benefit'] ??= $value['benefit'] ?? null;
                $definition['execution_difference'] ??= $value['difference'] ?? null;
                $written += $this->fillProject($project, array_filter($definition, fn ($item) => $item !== null));
                $this->markPrimaryService($project);
                $this->deriveSeoFields($project, $definition);
                $dated += $this->stampCompletedAt($project);
                $linked += $this->attachServices($project, $definition, $services);
                $faqs += $this->writeFaqs($project);
                $captions += $this->writeCaptions($project);
                $this->writePageSeo($project, $definition, $this->isIndexable($project));
            }
        }

        // Every project carries a real execution date, including the ones
        // with no cleared photograph: the date is a business record and it
        // is in the source_ref whether the project ships a page or not.
        foreach (Project::query()->whereNull('completed_at')->get() as $undated) {
            $dated += $this->stampCompletedAt($undated);
        }

        // Primary service applies to every project that has links, including
        // the drafts: the ranking is a data fact, not a publishing decision.
        foreach (Project::query()->get() as $anyProject) {
            $this->markPrimaryService($anyProject);

            // Cluster needs only the primary service, so every project can
            // have one - including the drafts that carry no case study yet.
            if (blank($anyProject->cluster) && $cluster = ProjectCluster::fromServiceSlug($this->primaryServiceSlug($anyProject))) {
                $anyProject->forceFill(['cluster' => $cluster->value])->save();
            }
        }

        $this->command?->info("Case studies: {$written} field group(s) written, {$dated} date(s) stamped, {$linked} service link(s) added, {$faqs} FAQ(s) added, {$captions} caption(s) written.");
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function fillProject(Project $project, array $definition): int
    {
        $fields = ['title', 'summary', 'challenge', 'site_condition', 'execution_steps', 'outcome',
            'client_problem', 'client_benefit', 'execution_difference'];
        $updates = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $definition) && blank($project->{$field})) {
                $updates[$field] = $definition[$field];
            }
        }

        if ($updates === []) {
            return 0;
        }

        $project->forceFill($updates)->save();

        return 1;
    }

    /**
     * The library's source_ref carries the day the work was photographed
     * (CAND-20260730-FACADE-B -> 2026-07-30). That is a record of when the
     * job happened, so it is used rather than invented - and only when the
     * column is still empty.
     */
    private function stampCompletedAt(Project $project): int
    {
        if (filled($project->completed_at) || ! preg_match('/(\d{8})/', (string) $project->source_ref, $match)) {
            return 0;
        }

        $date = Carbon::createFromFormat('Ymd', $match[1]);

        if (! $date || $date->isFuture()) {
            return 0;
        }

        $project->forceFill(['completed_at' => $date->startOfDay()])->save();

        return 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<string, int>  $services
     */
    private function attachServices(Project $project, array $definition, $services): int
    {
        $slugs = $definition['services'] ?? [];
        $ids = collect($slugs)->map(fn (string $slug) => $services[$slug] ?? null)->filter()->all();

        if ($ids === []) {
            return 0;
        }

        $before = $project->services()->count();
        $project->services()->syncWithoutDetaching($ids);

        return $project->services()->count() - $before;
    }

    /**
     * The focus keyword and the cluster, both derived from data that
     * already exists - the phrase this case study was written to own, and
     * the kind of work its primary service represents. Neither is
     * invented, and neither overwrites an editor's choice.
     *
     * @param  array<string, mixed>  $definition
     */
    private function deriveSeoFields(Project $project, array $definition): void
    {
        $updates = [];

        if (blank($project->focus_keyword) && filled($definition['keywords']['primary'] ?? null)) {
            $updates['focus_keyword'] = $definition['keywords']['primary'];
        }

        if (blank($project->cluster)) {
            $cluster = filled($definition['cluster'] ?? null)
                ? ProjectCluster::tryFrom($definition['cluster'])
                : ProjectCluster::fromServiceSlug($this->primaryServiceSlug($project));

            if ($cluster) {
                $updates['cluster'] = $cluster->value;
            }
        }

        if ($updates !== []) {
            $project->forceFill($updates)->save();
        }
    }

    private function primaryServiceSlug(Project $project): ?string
    {
        $serviceId = DB::table('project_service')
            ->where('project_id', $project->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->value('service_id');

        return $serviceId
            ? Page::query()->where('type', 'service')->where('pageable_id', $serviceId)->value('slug')
            : null;
    }

    /**
     * One service is the reason a case study exists; the others are what
     * else happened on site. Without this flag the page leads with
     * whichever row the database returned first. Only the existing links
     * are ranked - no service is ever added here.
     */
    private function markPrimaryService(Project $project): void
    {
        $links = DB::table('project_service')->where('project_id', $project->id)->orderBy('id')->get();

        if ($links->isEmpty() || $links->contains(fn ($link) => (bool) $link->is_primary)) {
            return;
        }

        DB::table('project_service')->where('id', $links->first()->id)->update(['is_primary' => true]);
    }

    /**
     * Captions for the photographs that carry an argument: the hero, and
     * the first image of the before and after stages. Never all of them -
     * a caption on every frame is noise that buries the few that matter.
     */
    private function writeCaptions(Project $project): int
    {
        $wanted = $this->captions[$project->source_ref] ?? [];

        if ($wanted === []) {
            return 0;
        }

        $rows = DB::table('project_media')
            ->join('media', 'media.id', '=', 'project_media.media_id')
            ->where('project_media.project_id', $project->id)
            ->orderBy('project_media.sort_order')
            ->get(['media.id', 'media.caption', 'project_media.stage']);

        $written = 0;
        $targets = [];

        foreach (['before', 'after'] as $stage) {
            if (isset($wanted[$stage]) && $first = $rows->firstWhere('stage', $stage)) {
                $targets[$first->id] = $wanted[$stage];
            }
        }

        // The hero is whatever the template opens on: the first "after"
        // when one exists, otherwise the first photograph in sort order.
        if (isset($wanted['hero'])) {
            $hero = $rows->firstWhere('stage', 'after') ?? $rows->first();

            if ($hero && ! isset($targets[$hero->id])) {
                $targets[$hero->id] = $wanted['hero'];
            }
        }

        foreach ($targets as $mediaId => $caption) {
            $current = $rows->firstWhere('id', $mediaId);

            if ($current && filled($current->caption)) {
                continue;
            }

            DB::table('media')->where('id', $mediaId)->update(['caption' => $caption, 'updated_at' => now()]);
            $written++;
        }

        return $written;
    }

    /**
     * Was this value written by the import rather than by a person?
     *
     * Blank counts, and so do the two templates the project importer
     * used. Anything else is treated as an editor's decision and left
     * exactly as it is.
     */
    private function isMachineDefault(?string $value, string $pageTitle): bool
    {
        if (blank($value)) {
            return true;
        }

        return str_starts_with($value, 'أعمال نفّذها فريق')
            || $value === $pageTitle.' | فايب كلين برو'
            || str_starts_with($value, 'توثيق مصوَّر لمشروع');
    }

    /**
     * Can this case study compete for its own long-tail query?
     *
     * The first cut of this system indexed by photo count alone, and that
     * was the wrong test: it held back seventeen pages that each carry
     * unique copy, a real photograph, a service and inbound links. A page
     * like that is short, not thin, and a short page that answers a
     * specific query is an asset - throwing it away costs rankings and
     * buys nothing.
     *
     * So the test is readiness, not volume. A case study is indexable when
     * it has all five of: a cleared photograph, its own challenge copy,
     * execution steps, a service it supports, and questions it answers.
     * Anything missing one of those is not ready to compete, and stays out
     * of the index until it is.
     */
    private function isIndexable(Project $project): bool
    {
        $page = Page::query()->where('type', 'project')->where('pageable_id', $project->id)->first();

        if (! $page) {
            return false;
        }

        $publishablePhotos = DB::table('project_media')
            ->join('media', 'media.id', '=', 'project_media.media_id')
            ->where('project_media.project_id', $project->id)
            ->whereIn('media.status', ['ready', 'replace'])
            ->count();

        return $publishablePhotos > 0
            && filled($project->challenge)
            && filled($project->execution_steps)
            && $project->services()->exists()
            && DB::table('faqs')->where('page_id', $page->id)->exists();
    }

    /**
     * The questions a case study answers, plus the block the template
     * needs to render them. A question query is the cheapest long-tail
     * entry a short case study can win, so this is not decoration: it is
     * what makes a 70-word project page worth indexing.
     */
    private function writeFaqs(Project $project): int
    {
        $bank = $this->faqBank[$project->source_ref] ?? [];
        $page = Page::query()->where('type', 'project')->where('pageable_id', $project->id)->first();

        if ($bank === [] || ! $page) {
            return 0;
        }

        $existing = DB::table('faqs')->where('page_id', $page->id)->pluck('question')->all();
        $added = 0;

        foreach ($bank as $index => [$question, $answer]) {
            if (in_array($question, $existing, true)) {
                continue;
            }

            DB::table('faqs')->insert([
                'page_id' => $page->id,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $index,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $added++;
        }

        // The template only renders the FAQ section when the page carries
        // a faq block, so add one the first time questions appear.
        $hasBlock = DB::table('content_blocks')->where('page_id', $page->id)->where('type', 'faq')->exists();

        $hasQuestions = DB::table('faqs')->where('page_id', $page->id)->where('is_active', true)->exists();

        if (! $hasBlock && $hasQuestions) {
            DB::table('content_blocks')->insert([
                'page_id' => $page->id,
                'type' => 'faq',
                'data' => json_encode(['section' => 'project-faq'], JSON_UNESCAPED_UNICODE),
                'position' => (int) DB::table('content_blocks')->where('page_id', $page->id)->max('position') + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $added;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function writePageSeo(Project $project, array $definition, bool $indexable): void
    {
        $page = Page::query()->where('type', 'project')->where('pageable_id', $project->id)->first();

        if (! $page) {
            return;
        }

        $seo = SeoMetadata::query()->firstOrNew(['page_id' => $page->id]);

        // The authored title and description replace a machine default,
        // never an editor's words. The defaults are recognisable: the
        // import stamped every project page with the same two templates,
        // which is how six case studies ended up sharing one title and
        // nine sharing one description - duplicate metadata that makes
        // Google collapse them against each other.
        if (filled($definition['meta_title'] ?? null) && $this->isMachineDefault($seo->meta_title, $page->title)) {
            $seo->meta_title = $definition['meta_title'];
        }

        if (filled($definition['meta_description'] ?? null) && $this->isMachineDefault($seo->meta_description, $page->title)) {
            $seo->meta_description = $definition['meta_description'];
        }

        // Indexability is the tier decision, so it is set rather than
        // defaulted: a Tier 1 case study belongs in the index, a Tier 2
        // one stays out of it until it has the media to deserve a place.
        // This is the one field the seeder owns; an editor can still flip
        // it in the panel, and re-running restores the tier's intent.
        $seo->robots_index = $indexable;

        $seo->robots_follow ??= true;
        $seo->page_id = $page->id;
        $seo->save();
    }
}
