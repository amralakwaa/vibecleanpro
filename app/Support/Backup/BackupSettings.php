<?php

namespace App\Support\Backup;

use App\Support\Settings\IntegrationSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

/**
 * Backup configuration from the panel, plus the real state of the backup
 * sets on disk.
 *
 * Nothing here is assumed: "last backup" is read from the sets that exist,
 * and a restore test only counts when someone records that they ran one
 * (see BACKUP_AND_RESTORE_RUNBOOK.md). Off-site backup reports itself as
 * not configured until a destination implementation exists.
 */
class BackupSettings
{
    public function __construct(
        private readonly IntegrationSettings $settings,
        private readonly ExternalBackupDestinationRegistry $destinations,
    ) {}

    public function localEnabled(): bool
    {
        return $this->settings->bool(IntegrationSettings::BACKUP_LOCAL_ENABLED);
    }

    public function dailyTime(): string
    {
        $time = $this->settings->string(IntegrationSettings::BACKUP_TIME) ?? '02:30';

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : '02:30';
    }

    public function keep(): int
    {
        return max(1, $this->settings->int(IntegrationSettings::BACKUP_KEEP, (int) config('backup.keep', 14)));
    }

    public function includesDatabase(): bool
    {
        return ! $this->settings->has(IntegrationSettings::BACKUP_INCLUDE_DATABASE) || $this->settings->bool(IntegrationSettings::BACKUP_INCLUDE_DATABASE);
    }

    public function includesMedia(): bool
    {
        return ! $this->settings->has(IntegrationSettings::BACKUP_INCLUDE_MEDIA) || $this->settings->bool(IntegrationSettings::BACKUP_INCLUDE_MEDIA);
    }

    public function externalEnabled(): bool
    {
        return $this->settings->bool(IntegrationSettings::BACKUP_EXTERNAL_ENABLED);
    }

    public function externalProvider(): string
    {
        return $this->settings->string(IntegrationSettings::BACKUP_EXTERNAL_PROVIDER) ?? ExternalBackupDestinationRegistry::LOCAL_ONLY;
    }

    public function externalDestination(): ?string
    {
        return $this->settings->string(IntegrationSettings::BACKUP_EXTERNAL_DESTINATION);
    }

    public function hasExternalCredentials(): bool
    {
        return $this->settings->hasSecret(IntegrationSettings::BACKUP_EXTERNAL_KEY)
            && $this->settings->hasSecret(IntegrationSettings::BACKUP_EXTERNAL_SECRET);
    }

    public function lastRestoreTestAt(): ?CarbonImmutable
    {
        $value = $this->settings->string(IntegrationSettings::BACKUP_LAST_RESTORE_TEST);

        return $value ? CarbonImmutable::parse($value) : null;
    }

    public function recordRestoreTest(): void
    {
        $this->settings->set(IntegrationSettings::BACKUP_LAST_RESTORE_TEST, now()->toIso8601String());
    }

    /**
     * The newest backup set on disk, read from its own manifest.
     *
     * @return array{name: string, taken_at: ?CarbonImmutable, bytes: int, files: list<string>, checksums: bool}|null
     */
    public function latestSet(): ?array
    {
        $disk = Storage::disk(config('backup.disk'));
        $directory = trim((string) config('backup.directory'), '/');
        $latest = collect($disk->directories($directory))->sort()->last();

        if (! $latest) {
            return null;
        }

        $manifest = rescue(fn () => json_decode((string) $disk->get($latest.'/manifest.json'), true), null, false);
        $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];

        return [
            'name' => basename($latest),
            'taken_at' => rescue(fn () => CarbonImmutable::createFromFormat('Y-m-d_His', basename($latest)) ?: null, null, false),
            'bytes' => (int) collect($files)->sum('bytes'),
            'files' => array_keys($files),
            'checksums' => collect($files)->every(fn (array $file) => filled($file['sha256'] ?? null)),
        ];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function localReadiness(): array
    {
        $latest = $this->latestSet();

        if (! $latest) {
            return ['state' => 'not_configured', 'label' => 'لا توجد نسخة', 'detail' => 'لم تُنشأ أي نسخة احتياطية بعد (backup:run).'];
        }

        $fresh = $latest['taken_at'] && $latest['taken_at']->greaterThan(now()->subHours(26));
        $size = number_format($latest['bytes'] / 1048576, 1).' MB';
        $restore = $this->lastRestoreTestAt();
        $restoreNote = $restore ? 'آخر اختبار استعادة: '.$restore->format('Y-m-d').'.' : 'لم يُسجَّل اختبار استعادة بعد.';

        if (! $this->localEnabled()) {
            return ['state' => 'configured', 'label' => 'متوقف', 'detail' => "الجدولة متوقفة. آخر نسخة: {$latest['name']} ({$size}). {$restoreNote}"];
        }

        return [
            'state' => $fresh ? 'ready' : 'error',
            'label' => $fresh ? 'جاهز' : 'النسخة قديمة',
            'detail' => "آخر نسخة: {$latest['name']} ({$size})، بصمات التحقق ".($latest['checksums'] ? 'مكتملة' : 'ناقصة').". {$restoreNote}",
        ];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function externalReadiness(): array
    {
        $provider = $this->externalProvider();

        if (! $this->externalEnabled() || $provider === ExternalBackupDestinationRegistry::LOCAL_ONLY) {
            return ['state' => 'not_configured', 'label' => 'غير مهيأ', 'detail' => 'النسخ خارج الخادم غير مفعّل — النسخ الحالية على الخادم نفسه فقط.'];
        }

        if (! $this->destinations->has($provider)) {
            return ['state' => 'error', 'label' => 'بانتظار التنفيذ', 'detail' => 'اختيرت وجهة ('.($this->destinations->options()[$provider] ?? $provider).') لكن لا يوجد تنفيذ لها بعد؛ النسخ لا تُرفع تلقائيًا. يحتاج حزمة وموافقتك.'];
        }

        return $this->destinations->get($provider)->isOperational($this)
            ? ['state' => 'ready', 'label' => 'جاهز', 'detail' => 'النسخ تُرفع إلى: '.($this->externalDestination() ?? $provider).'.']
            : ['state' => 'error', 'label' => 'ناقص', 'detail' => 'بيانات الوجهة أو مفاتيحها ناقصة.'];
    }
}
