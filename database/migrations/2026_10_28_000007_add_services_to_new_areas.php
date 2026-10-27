<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Links all 19 services to the 5 new tier-B area pages.
 * Mirrors الرمال (30) and المونسية (40) which both carry all 19 services.
 * Without these entries the "الخدمات المتاحة هنا" section renders empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();
        $newAreaIds = [88, 89, 90, 91, 92];
        $allServiceIds = DB::table('services')->pluck('id')->toArray();

        foreach ($newAreaIds as $areaId) {
            foreach ($allServiceIds as $serviceId) {
                DB::table('service_area')->insertOrIgnore([
                    'area_id' => $areaId,
                    'service_id' => $serviceId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('service_area')->whereIn('area_id', [88, 89, 90, 91, 92])->delete();
    }
};
