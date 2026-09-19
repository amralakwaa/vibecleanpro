<?php

namespace Database\Seeders;

use App\Console\Commands\ImportMediaLibrary;
use App\Enums\AreaTier;
use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Enums\ServiceCapability;
use App\Enums\ServicePricingMode;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\BusinessProfile;
use App\Models\InternalLink;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Loads the approved production structure (database/seeders/content/
 * production-content.json, generated from the SEO architecture documents):
 * 17 services, 86 areas in 6 direction groups, 47 project candidates from
 * the company's own photo library and the trust pages.
 *
 * Everything lands as a DRAFT. Nothing is published by this seeder -
 * publishing goes through the Publishing Gate (capability, tier, media
 * readiness, owner confirmation), one page at a time.
 *
 * Idempotent: a service/area/page is matched by its slug, a project by its
 * library group id; anything that already exists is left exactly as an
 * editor may have changed it. Unwritten pages get SEO fields but no body,
 * so the Gate's "content empty" error keeps them from going live empty.
 */
class ProductionContentSeeder extends Seeder
{
    private const BRAND = ' | فايب كلين برو';

    /** @var array<string, int> */
    private array $mediaIds = [];

    public function run(): void
    {
        $content = json_decode((string) file_get_contents(database_path('seeders/content/production-content.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->mediaIds = Media::query()
            ->where('path', 'like', ImportMediaLibrary::DIRECTORY.'/%')
            ->pluck('id', 'original_filename')
            ->all();

        $categories = collect($content['categories'])->mapWithKeys(fn (array $category) => [
            $category['key'] => ServiceCategory::query()->firstOrCreate(['slug' => $category['slug']], ['name' => $category['name'], 'sort_order' => $category['sort_order']])->id,
        ]);

        $services = collect($content['services'])->mapWithKeys(fn (array $data) => [$data['slug'] => $this->service($data, $categories->all())])->filter();
        $this->serviceLinks($content['services'], $services->all());

        $groups = collect($content['area_groups'])->mapWithKeys(fn (array $group) => [
            $group['slug'] => AreaGroup::query()->firstOrCreate(['slug' => $group['slug']], ['name' => $group['name'], 'sort_order' => $group['sort_order']])->id,
        ]);

        $available = $services->filter(fn (Service $service) => $service->capability_status === ServiceCapability::Available);

        foreach ($content['areas'] as $data) {
            $this->area($data, $groups->all(), $services->all(), $available->all());
        }

        foreach ($content['projects'] as $data) {
            $this->project($data, $services->all());
        }

        foreach ($content['trust_pages'] as $data) {
            $this->trustPage($data);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $categories
     */
    private function service(array $data, array $categories): ?Service
    {
        $existing = Page::query()->where('slug', $data['slug'])->first();

        if ($existing) {
            return $existing->pageable instanceof Service ? $existing->pageable : null;
        }

        $service = Service::query()->create([
            'service_category_id' => $categories[$data['category']],
            'featured_media_id' => $this->mediaId($data['featured_media_file']),
            'name' => $data['name'],
            'short_description' => $data['short_description'],
            'pricing_mode' => ServicePricingMode::QuoteOnly,
            'show_price' => false,
            'capability_status' => ServiceCapability::from($data['capability']),
            'is_featured' => $data['is_featured'],
            'sort_order' => $data['sort_order'],
        ]);

        $page = $this->draftPage($service, PageType::Service, $data['slug'], $data['page']['title'], $data['page']['meta_title'], $data['page']['meta_description']);

        foreach ($page ? $data['blocks'] : [] as $position => $block) {
            $page->contentBlocks()->create(['type' => $block['type'], 'data' => $this->blockData($block), 'position' => $position + 1, 'is_active' => true]);
        }

        foreach ($page ? $data['faqs'] : [] as $position => $faq) {
            $page->faqs()->create(['question' => $faq['question'], 'answer' => $faq['answer'], 'sort_order' => $position + 1, 'is_active' => true]);
        }

        return $service;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, Service>  $services
     */
    private function serviceLinks(array $rows, array $services): void
    {
        foreach ($rows as $row) {
            $from = ($services[$row['slug']] ?? null)?->page;

            foreach ($row['links'] as $position => $targetSlug) {
                $to = ($services[$targetSlug] ?? null)?->page;

                if (! $from || ! $to || InternalLink::query()->where('from_page_id', $from->id)->where('to_page_id', $to->id)->exists()) {
                    continue;
                }

                (new InternalLink)->forceFill([
                    'from_page_id' => $from->id,
                    'to_page_id' => $to->id,
                    'anchor_text' => $to->title,
                    'context' => 'related_services',
                    'sort_order' => $position + 1,
                    'is_active' => true,
                ])->save();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $groups
     * @param  array<string, Service>  $services
     * @param  array<string, Service>  $available
     */
    private function area(array $data, array $groups, array $services, array $available): void
    {
        $area = Area::query()->withTrashed()->firstOrCreate(['slug' => $data['slug']], [
            'name' => $data['name'],
            'area_group_id' => $groups[$data['group']],
            'tier' => AreaTier::from($data['tier']),
            'sort_order' => $data['sort_order'],
        ]);

        if (! $area->wasRecentlyCreated || $area->tier === AreaTier::C) {
            return;
        }

        // Tier A lists the services its demand evidence names; Tier B lists
        // what the company offers everywhere. Only services confirmed as
        // available are attached - never a service awaiting confirmation.
        $slugs = $area->tier === AreaTier::A ? $data['services'] : array_keys($available);
        $area->services()->syncWithoutDetaching(
            collect($slugs)->unique()->filter(fn (string $slug) => isset($available[$slug]))->mapWithKeys(fn (string $slug) => [$services[$slug]->id => ['is_active' => true]])->all()
        );

        $page = $this->draftPage($area, PageType::Area, $data['slug'], $data['page_title'], $data['page_title'].self::BRAND, null);

        if ($page && $area->tier === AreaTier::B) {
            $group = AreaGroup::query()->find($groups[$data['group']])?->name;
            $page->contentBlocks()->create(['type' => 'rich_text', 'position' => 1, 'is_active' => true, 'data' => ['content' => sprintf(
                '<p>تغطي فايب كلين برو حي %s ضمن %s. اختر الخدمة التي تحتاجها من القائمة أدناه، وأرسل لنا صورة المكان وموقعه عبر واتساب؛ نرتب المعاينة ونرسل لك عرض سعر مكتوبًا قبل التنفيذ. لا يتم الدفع عبر الموقع.</p>',
                e($data['name']),
                e((string) $group),
            )]]);
            $page->contentBlocks()->create(['type' => 'cta', 'position' => 2, 'is_active' => true, 'data' => [
                'heading' => 'تحتاج خدمة تنظيف في '.$data['name'].'؟',
                'button_label' => 'اطلب عرض سعر',
                'button_url' => route('public.quote', ['area' => $area->id]),
            ]]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, Service>  $services
     */
    private function project(array $data, array $services): void
    {
        $project = Project::query()->withTrashed()->firstOrCreate(['source_ref' => $data['source_ref']], [
            'title' => $data['title'],
            'area_id' => null,
            'summary' => null,
            'completed_at' => null,
            'owner_confirmed_at' => null,
        ]);

        if (! $project->wasRecentlyCreated) {
            return;
        }

        if ($data['service'] && isset($services[$data['service']])) {
            $project->services()->syncWithoutDetaching([$services[$data['service']]->id]);
        }

        $attach = [];

        foreach ($data['media'] as $position => $item) {
            $mediaId = $this->mediaId($item['file']);

            // Private (held) photos never join a project, even a draft one.
            if ($mediaId && Media::query()->whereKey($mediaId)->where('status', '!=', 'private')->exists()) {
                $attach[$mediaId] = ['stage' => $item['stage'], 'sort_order' => $position + 1];
            }
        }

        $project->media()->syncWithoutDetaching($attach);

        // Only what is known: the service, the city and the stages the
        // photos show. District, date and results wait for the owner.
        $service = $data['service'] ? ($services[$data['service']] ?? null) : null;
        $stageNames = ['before' => 'قبل', 'during' => 'أثناء', 'after' => 'بعد'];
        $stages = collect($attach)->pluck('stage')->unique()->map(fn (?string $stage) => $stageNames[$stage] ?? null)->filter()->implode('، ');
        $summary = sprintf(
            'أعمال نفّذها فريق فايب كلين برو%s في الرياض.%s',
            $service ? ' ضمن خدمة '.$service->name : '',
            $stages !== '' ? ' الصور توثّق مراحل العمل: '.$stages.'.' : '',
        );
        $project->update(['summary' => $summary]);

        $page = $this->draftPage($project, PageType::Project, $data['slug'], $data['title'], $data['title'].self::BRAND, $summary);

        if (! $page) {
            return;
        }

        $page->contentBlocks()->create(['type' => 'rich_text', 'position' => 1, 'is_active' => true, 'data' => [
            'content' => '<p>'.e($summary).' نرسل لك عرض سعر مكتوبًا بعد المعاينة، ولا يتم الدفع عبر الموقع.</p>',
        ]]);

        if ($service?->page) {
            $page->contentBlocks()->create(['type' => 'cta', 'position' => 2, 'is_active' => true, 'data' => [
                'heading' => 'تحتاج '.$service->name.'؟',
                'button_label' => 'تفاصيل الخدمة',
                'button_url' => url('/services/'.$service->page->slug),
            ]]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function trustPage(array $data): void
    {
        if (Page::query()->where('slug', $data['slug'])->exists()) {
            return;
        }

        $page = Page::query()->create([
            'type' => PageType::from($data['type']),
            'title' => $data['title'],
            'slug' => $data['slug'],
            'status' => PageStatus::Draft,
        ]);

        $page->seoMetadata()->create(['meta_title' => $data['meta_title'], 'meta_description' => $data['meta_description'], 'robots_index' => true, 'robots_follow' => true]);
    }

    private function draftPage(Service|Area|Project $owner, PageType $type, string $slug, string $title, ?string $metaTitle, ?string $metaDescription): ?Page
    {
        // A slug already taken by another record is left for an editor to resolve.
        if (Page::query()->where('slug', $slug)->exists()) {
            return null;
        }

        $page = new Page(['type' => $type, 'title' => $title, 'slug' => $slug, 'status' => PageStatus::Draft]);
        $owner->page()->save($page);

        $page->seoMetadata()->create([
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            // Tier B areas are noindex by rule; project pages stay noindex
            // until the owner adds their district, date and scope, because
            // until then their text is near-identical from one to the next.
            'robots_index' => ! ($owner instanceof Project || ($owner instanceof Area && $owner->tier === AreaTier::B)),
            'robots_follow' => true,
        ]);

        return $page;
    }

    /**
     * @param  array{type: string, data: array<string, mixed>}  $block
     * @return array<string, mixed>
     */
    private function blockData(array $block): array
    {
        $data = $block['data'];

        if ($block['type'] === 'image') {
            return ['media_id' => (string) $this->mediaId($data['media_file']), 'caption' => $data['caption'] ?? null];
        }

        if ($block['type'] === 'cta' && $data['button_url'] === '{whatsapp}') {
            $data['button_url'] = BusinessProfile::query()->first()?->whatsappUrl() ?? route('public.quote');
        }

        return $data;
    }

    private function mediaId(?string $file): ?int
    {
        return $file ? ($this->mediaIds[$file] ?? null) : null;
    }
}
