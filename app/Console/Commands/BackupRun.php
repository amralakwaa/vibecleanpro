<?php

namespace App\Console\Commands;

use App\Support\Backup\BackupSettings;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Writes one backup set - a gzipped MySQL dump, a zip of the uploaded
 * media originals and a manifest with SHA-256 checksums - to
 * {backup.disk}/{backup.directory}/{timestamp}/, then keeps only the
 * newest {backup.keep} sets.
 *
 * The database password reaches mysqldump through the MYSQL_PWD
 * environment variable, never the command line or the output.
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run {--skip-media : Database only}';

    protected $description = 'Back up the database and uploaded media to local storage, with checksums and retention';

    public function handle(BackupSettings $settings): int
    {
        $disk = Storage::disk(config('backup.disk'));
        $set = trim(config('backup.directory'), '/').'/'.now()->format('Y-m-d_His');
        $disk->makeDirectory($set);

        try {
            $files = [];

            if ($settings->includesDatabase()) {
                $files['database.sql.gz'] = $this->dumpDatabase($disk, $set);
            }

            if (! $this->option('skip-media') && $settings->includesMedia()) {
                $files['media.zip'] = $this->archiveMedia($disk, $set);
            }

            if ($files === []) {
                throw new RuntimeException('Both the database and the media are excluded - nothing to back up.');
            }
        } catch (RuntimeException $exception) {
            $disk->deleteDirectory($set);
            Log::error('Backup failed; the partial backup set was removed.', ['error' => $exception->getMessage()]);
            $this->error('Backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $manifest = [
            'created_at' => now()->toIso8601String(),
            'app_url' => config('app.url'),
            'database' => config('database.connections.'.config('database.default').'.database'),
            'files' => collect($files)->map(fn (string $path) => [
                'bytes' => $disk->size($path),
                'sha256' => hash_file('sha256', $disk->path($path)),
            ])->all(),
        ];
        $disk->put($set.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $removed = $this->prune($disk, $settings->keep());

        if ($settings->externalEnabled() && $settings->externalReadiness()['state'] !== 'ready') {
            $this->warn('Off-site copy is switched on but no destination implementation is available: this set stays on this server only.');
        }

        $this->info("Backup written to {$set} (".implode(', ', array_keys($files)).'). Old sets removed: '.$removed.'.');

        return self::SUCCESS;
    }

    private function dumpDatabase(Filesystem $disk, string $set): string
    {
        $connection = config('database.connections.'.config('database.default'));

        if (($connection['driver'] ?? null) !== 'mysql' && ($connection['driver'] ?? null) !== 'mariadb') {
            throw new RuntimeException('backup:run supports MySQL/MariaDB only.');
        }

        $result = Process::env(['MYSQL_PWD' => (string) ($connection['password'] ?? '')])
            ->timeout(600)
            ->run([
                config('backup.mysqldump_path'),
                '--single-transaction',
                '--routines',
                '--no-tablespaces',
                '--default-character-set=utf8mb4',
                '--host='.$connection['host'],
                '--port='.$connection['port'],
                '--user='.$connection['username'],
                $connection['database'],
            ]);

        if ($result->failed() || trim($result->output()) === '') {
            throw new RuntimeException('mysqldump failed: '.trim($result->errorOutput() ?: 'empty output'));
        }

        $path = $set.'/database.sql.gz';
        $disk->put($path, gzencode($result->output(), 9));

        return $path;
    }

    private function archiveMedia(Filesystem $disk, string $set): string
    {
        $media = Storage::disk(config('backup.media_disk'));
        $exclude = '/'.config('backup.media_exclude_segment').'/';
        $path = $set.'/media.zip';

        $zip = new ZipArchive;

        if ($zip->open($disk->path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the media archive.');
        }

        foreach ($media->allFiles(config('backup.media_directory')) as $file) {
            if (! str_contains('/'.$file, $exclude)) {
                $zip->addFile($media->path($file), $file);
            }
        }

        // An empty archive is still a valid (empty) media backup.
        if ($zip->numFiles === 0) {
            $zip->addFromString('.empty', '');
        }

        if (! $zip->close()) {
            throw new RuntimeException('Could not finish the media archive.');
        }

        return $path;
    }

    private function prune(Filesystem $disk, int $keep): int
    {
        $sets = collect($disk->directories(trim(config('backup.directory'), '/')))->sort()->values();
        $old = $sets->slice(0, max(0, $sets->count() - max(1, $keep)));

        $old->each(fn (string $directory) => $disk->deleteDirectory($directory));

        return $old->count();
    }
}
