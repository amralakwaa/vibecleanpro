<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company identity lives on the single business_profiles row because it
 * is 1:1 with the company and already the one source of truth for name,
 * phone, city and logo - an About page must never carry a second copy
 * of those. The founder is likewise 1:1 with the company (no list, no
 * reordering), so founder_* columns belong here rather than in a table
 * of one row. Values are a small ordered list edited as one unit and
 * never queried on their own, hence JSON rather than a table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('city');
            $table->text('identity_statement')->nullable()->after('tagline');
            $table->longText('story')->nullable()->after('identity_statement');
            $table->text('mission')->nullable()->after('story');
            $table->text('vision')->nullable()->after('mission');
            $table->json('values')->nullable()->after('vision');

            $table->string('founder_name')->nullable()->after('values');
            $table->string('founder_title')->nullable()->after('founder_name');
            $table->foreignId('founder_photo_media_id')->nullable()->after('founder_title')->constrained('media')->nullOnDelete();
            $table->text('founder_bio')->nullable()->after('founder_photo_media_id');
            $table->longText('founder_long_bio')->nullable()->after('founder_bio');

            $table->boolean('show_founder')->default(true)->after('founder_long_bio');
            $table->boolean('show_team')->default(true)->after('show_founder');
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('founder_photo_media_id');
            $table->dropColumn([
                'tagline', 'identity_statement', 'story', 'mission', 'vision', 'values',
                'founder_name', 'founder_title', 'founder_bio', 'founder_long_bio',
                'show_founder', 'show_team',
            ]);
        });
    }
};
