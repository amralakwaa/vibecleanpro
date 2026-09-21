<?php

namespace App\Console\Commands;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gives a cleared photograph the alt text it is missing, then lets it
 * become publishable.
 *
 * The media import left most of the library at `pending` for one reason:
 * no alt text. That is not a privacy problem - those files are already
 * `privacy_status = cleared` - it is an accessibility and SEO gap, and it
 * was holding real project evidence off the site.
 *
 * The alt text is not written here: it is derived from the file's own
 * `verified_description`, which is the record of what the photograph
 * actually shows. Nothing is invented, and a file with no verified
 * description is left alone - there is nothing truthful to say about it
 * yet.
 *
 * Readiness itself is still decided by Media::readinessProblem(); this
 * command only removes the one blocker it can remove honestly, and lets
 * the model refuse anything else.
 */
class BackfillMediaAltText extends Command
{
    protected $signature = 'media:backfill-alt-text {--dry-run : List what would change and write nothing}';

    protected $description = 'Derive alt text from each cleared photo\'s verified description so it can be published';

    public function handle(): int
    {
        $candidates = Media::query()
            ->where('status', MediaStatus::Pending)
            ->where('privacy_status', MediaPrivacyStatus::Cleared)
            ->whereNotNull('verified_description')
            ->where('verified_description', '!=', '')
            ->whereNull('alt_text')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No cleared photo is waiting on alt text.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $written = 0;
        $refused = 0;

        $arabic = require database_path('seeders/content/media-alt-ar.php');
        $untranslated = [];

        foreach ($candidates as $media) {
            $description = trim(preg_replace('/\s+/u', ' ', (string) $media->verified_description));
            $alt = $arabic[$description] ?? null;

            // The site is Arabic. An English alt text helps neither a
            // screen-reader user nor Arabic search, so a description with
            // no translation yet is left pending rather than shipped in
            // the wrong language.
            if (blank($alt)) {
                if (! $this->isArabic($description)) {
                    $untranslated[$description] = true;

                    continue;
                }

                $alt = $this->altFrom($description);
            }

            if ($alt === '') {
                continue;
            }

            if ($dryRun) {
                $this->line("  {$media->original_filename}: {$alt}");
                $written++;

                continue;
            }

            $media->alt_text = $alt;
            $media->status = MediaStatus::Ready;

            try {
                $media->save();
                $written++;
            } catch (ValidationException $e) {
                // The model refused it for a reason this command cannot
                // fix - a missing description on an illustration, say.
                // Leave it pending and keep the reason visible.
                $refused++;
                $this->warn("  {$media->original_filename}: ".collect($e->errors())->flatten()->first());
            }
        }

        $verb = $dryRun ? 'would be given' : 'given';
        $this->info("{$written} photo(s) {$verb} alt text and marked ready; {$refused} refused by the readiness rule.");

        if ($untranslated !== []) {
            $this->warn(count($untranslated).' description(s) have no Arabic translation yet and stay pending:');

            foreach (array_slice(array_keys($untranslated), 0, 10) as $missing) {
                $this->line("  - {$missing}");
            }

            $this->line('  Add them to database/seeders/content/media-alt-ar.php.');
        }

        return self::SUCCESS;
    }

    private function isArabic(string $text): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $text);
    }

    /**
     * One sentence of plain description, capped so it stays an alt text
     * and not a paragraph. No brand name, no claim - the rule is that alt
     * describes the picture and nothing else.
     */
    private function altFrom(string $description): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $description));
        $firstSentence = preg_split('/(?<=[.。!؟?])\s+/u', $text)[0] ?? $text;
        $alt = trim($firstSentence, " \t\n\r\0\x0B.");

        return Str::limit($alt, 120, '');
    }
}
