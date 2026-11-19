<?php

namespace Tests\Feature;

use App\Support\Media\VideoEvidence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Field clips must be visible to search engines (a real <video> in the
 * page and a VideoObject) while still downloading nothing before play.
 */
class VideoEvidenceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_clip_renders_a_preload_none_video_and_its_video_object(): void
    {
        if (! is_file(VideoEvidence::videoPath('team'))) {
            $this->markTestSkipped('Field clips are not present in this checkout.');
        }

        $html = Blade::render('<x-public.video-evidence slug="team" caption="فريق العمل" />');

        $this->assertMatchesRegularExpression('#<video[^>]+src="/storage/media/videos/team\.mp4"[^>]+preload="none"#', $html);
        $this->assertStringNotContainsString('<template', $html);
        $this->assertStringContainsString('"@type":"VideoObject"', $html);
        $this->assertStringContainsString('"name":"فريق العمل"', $html);
        $this->assertMatchesRegularExpression('/"duration":"PT\d+S"/', $html);
        $this->assertStringContainsString('/storage/media/videos/posters/team.webp', $html);
    }

    public function test_a_missing_clip_states_no_video_object(): void
    {
        $this->assertNull(VideoEvidence::schema('does-not-exist'));
    }
}
