<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Owner confirmed (2026-10-28) the real warranty is the one on /warranty:
 * a free re-clean when reported within 24 hours of handover. The "up to
 * 10 years" claim on the home/about trust points and three service pages
 * contradicted that page, so it is replaced with the same commitment.
 */
return new class extends Migration
{
    private const OLD_ITEM = '<strong>ضمان يصل إلى 10 سنوات</strong> حسب نوع الخدمة ونطاق التنفيذ، وتُذكر مدته في عرض السعر';

    private const NEW_ITEM = '<strong>ضمان إعادة التنظيف</strong>: إن لم تطابق النتيجة نطاق العمل المتفق عليه نعيد تنظيف الجزء محل الملاحظة مجانًا، عند الإبلاغ خلال 24 ساعة من التسليم';

    /** @var array{title: string, description: string} */
    private const OLD_POINT = ['title' => 'ضمان يصل إلى 10 سنوات', 'description' => 'نقدم ضمانًا يصل إلى 10 سنوات حسب نوع الخدمة ونطاق التنفيذ وشروط الضمان.'];

    /** @var array{title: string, description: string} */
    private const NEW_POINT = ['title' => 'ضمان إعادة التنظيف خلال 24 ساعة', 'description' => 'إن لم تطابق النتيجة نطاق العمل المتفق عليه نعيد تنظيف الجزء محل الملاحظة مجانًا، عند الإبلاغ خلال 24 ساعة من التسليم.'];

    public function up(): void
    {
        $this->replaceInBlocks(self::OLD_ITEM, self::NEW_ITEM);
        $this->replaceTrustPoint(self::OLD_POINT, self::NEW_POINT);
    }

    public function down(): void
    {
        $this->replaceInBlocks(self::NEW_ITEM, self::OLD_ITEM);
        $this->replaceTrustPoint(self::NEW_POINT, self::OLD_POINT);
    }

    private function replaceInBlocks(string $search, string $replace): void
    {
        foreach (DB::table('content_blocks')->where('type', 'rich_text')->get() as $block) {
            $data = json_decode($block->data, true);
            $content = (string) ($data['content'] ?? '');

            if (! str_contains($content, $search)) {
                continue;
            }

            $data['content'] = str_replace($search, $replace, $content);
            DB::table('content_blocks')->where('id', $block->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }

    /**
     * @param  array{title: string, description: string}  $from
     * @param  array{title: string, description: string}  $to
     */
    private function replaceTrustPoint(array $from, array $to): void
    {
        $profile = DB::table('business_profiles')->orderBy('id')->first();
        $points = $profile ? json_decode((string) $profile->trust_points, true) : null;

        if (! is_array($points)) {
            return;
        }

        $changed = false;

        foreach ($points as $i => $point) {
            if (($point['title'] ?? null) === $from['title']) {
                $points[$i] = array_merge($point, $to);
                $changed = true;
            }
        }

        if ($changed) {
            DB::table('business_profiles')->where('id', $profile->id)->update(['trust_points' => json_encode($points, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }
};
