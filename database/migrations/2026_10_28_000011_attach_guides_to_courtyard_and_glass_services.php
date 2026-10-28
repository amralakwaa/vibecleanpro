<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Service page slug => article page slug. Both pages exist on production;
     * the service pages had no guide attached although a matching one existed.
     *
     * @var array<string, string>
     */
    private const LINKS = [
        'courtyard-cleaning' => 'courtyard-interlock-cleaning',
        'glass-cleaning' => 'streak-free-glass-cleaning-riyadh',
    ];

    public function up(): void
    {
        foreach (self::LINKS as $serviceSlug => $articleSlug) {
            [$serviceId, $articleId] = $this->ids($serviceSlug, $articleSlug);

            if ($serviceId === null || $articleId === null) {
                continue;
            }

            $exists = DB::table('article_service')
                ->where('article_id', $articleId)
                ->where('service_id', $serviceId)
                ->exists();

            if (! $exists) {
                DB::table('article_service')->insert([
                    'article_id' => $articleId,
                    'service_id' => $serviceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::LINKS as $serviceSlug => $articleSlug) {
            [$serviceId, $articleId] = $this->ids($serviceSlug, $articleSlug);

            DB::table('article_service')
                ->where('article_id', $articleId)
                ->where('service_id', $serviceId)
                ->delete();
        }
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function ids(string $serviceSlug, string $articleSlug): array
    {
        return [
            DB::table('pages')->where('slug', $serviceSlug)->where('pageable_type', 'service')->value('pageable_id'),
            DB::table('pages')->where('slug', $articleSlug)->where('pageable_type', 'article')->value('pageable_id'),
        ];
    }
};
