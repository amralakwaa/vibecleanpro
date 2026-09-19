<?php

namespace Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupRunTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('media/vibe/facade.webp', 'original-bytes');
        Storage::disk('public')->put('media/vibe/responsive/facade-480w.webp', 'derived-bytes');
        config(['database.connections.mysql.password' => 'db-secret', 'backup.keep' => 2]);
    }

    private function latestSet(): string
    {
        return collect(Storage::disk('local')->directories('backups'))->sort()->last();
    }

    public function test_a_backup_set_holds_the_dump_the_media_originals_and_checksums(): void
    {
        Process::fake(['*' => Process::result(output: "-- dump\nCREATE TABLE pages (id int);\n")]);

        $this->artisan('backup:run')->assertSuccessful();

        $disk = Storage::disk('local');
        $set = $this->latestSet();
        $this->assertSame("-- dump\nCREATE TABLE pages (id int);\n", gzdecode($disk->get($set.'/database.sql.gz')));

        $zip = new ZipArchive;
        $zip->open($disk->path($set.'/media.zip'));
        $this->assertNotFalse($zip->locateName('media/vibe/facade.webp'));
        $this->assertFalse($zip->locateName('media/vibe/responsive/facade-480w.webp'));
        $zip->close();

        $manifest = json_decode($disk->get($set.'/manifest.json'), true);
        $this->assertSame(hash_file('sha256', $disk->path($set.'/database.sql.gz')), $manifest['files']['database.sql.gz']['sha256']);
        $this->assertArrayHasKey('media.zip', $manifest['files']);
    }

    public function test_the_database_password_never_appears_on_the_command_line(): void
    {
        Process::fake(['*' => Process::result(output: 'dump')]);

        $this->artisan('backup:run', ['--skip-media' => true])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $process) => ! str_contains(implode(' ', (array) $process->command), 'db-secret')
            && ($process->environment['MYSQL_PWD'] ?? null) === 'db-secret');
    }

    public function test_a_failed_dump_fails_the_run_and_leaves_no_partial_set(): void
    {
        Process::fake(['*' => Process::result(output: '', errorOutput: 'Access denied', exitCode: 2)]);

        $this->artisan('backup:run')->assertFailed();

        $this->assertSame([], Storage::disk('local')->directories('backups'));
    }

    public function test_only_the_newest_sets_are_kept(): void
    {
        Process::fake(['*' => Process::result(output: 'dump')]);
        foreach (['2026-01-01_000000', '2026-01-02_000000', '2026-01-03_000000'] as $old) {
            Storage::disk('local')->put("backups/{$old}/manifest.json", '{}');
        }

        $this->artisan('backup:run', ['--skip-media' => true])->assertSuccessful();

        $sets = collect(Storage::disk('local')->directories('backups'))->sort()->values();
        $this->assertCount(2, $sets);
        $this->assertSame('backups/2026-01-03_000000', $sets->first());
    }
}
