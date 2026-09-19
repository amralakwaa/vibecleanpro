<?php

namespace App\Support\Tracking;

use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Models\Area;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Models\Service;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read-only numbers for the admin dashboard, all from first-party data
 * (conversion_events + leads). Traffic and rankings live in Search Console
 * and GA4; nothing here is estimated.
 */
class ConversionReport
{
    public function __construct(private int $days = 30) {}

    /**
     * @return array<string, int> event type value => count
     */
    public function totals(): array
    {
        $counts = $this->events()->selectRaw('event_type, count(*) as total')->groupBy('event_type')->pluck('total', 'event_type');

        return collect(ConversionEventType::cases())->mapWithKeys(fn (ConversionEventType $type) => [$type->value => (int) ($counts[$type->value] ?? 0)])->all();
    }

    public function leadCount(): int
    {
        return Lead::query()->where('created_at', '>=', $this->since())->where('status', '!=', LeadStatus::Spam)->count();
    }

    public function wonCount(): int
    {
        return Lead::query()->where('created_at', '>=', $this->since())->whereIn('status', [LeadStatus::Won, LeadStatus::Completed])->count();
    }

    /**
     * @return Collection<int, array{name: string, total: int}>
     */
    public function topServices(int $limit = 5): Collection
    {
        return $this->topBy('service_id', Service::class, $limit);
    }

    /**
     * @return Collection<int, array{name: string, total: int}>
     */
    public function topAreas(int $limit = 5): Collection
    {
        return $this->topBy('area_id', Area::class, $limit);
    }

    /**
     * @return Collection<int, array{name: string, total: int}>
     */
    public function bySource(): Collection
    {
        return $this->events()
            ->selectRaw('source, count(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(fn (ConversionEvent $row) => ['name' => (string) ($row->source ?: 'direct'), 'total' => (int) $row->total]);
    }

    public function days(): int
    {
        return $this->days;
    }

    /**
     * @param  class-string<Service|Area>  $model
     * @return Collection<int, array{name: string, total: int}>
     */
    private function topBy(string $column, string $model, int $limit): Collection
    {
        $rows = $this->events()->whereNotNull($column)
            ->selectRaw("{$column} as subject_id, count(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $names = $model::query()->withTrashed()->whereKey($rows->pluck('subject_id'))->pluck('name', 'id');

        return $rows->map(fn (ConversionEvent $row) => ['name' => (string) ($names[$row->subject_id] ?? '—'), 'total' => (int) $row->total]);
    }

    /**
     * @return Builder<ConversionEvent>
     */
    private function events(): Builder
    {
        return ConversionEvent::query()->where('created_at', '>=', $this->since());
    }

    private function since(): CarbonInterface
    {
        return now()->subDays($this->days)->startOfDay();
    }
}
