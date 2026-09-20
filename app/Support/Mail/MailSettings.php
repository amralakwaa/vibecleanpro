<?php

namespace App\Support\Mail;

use App\Models\BusinessProfile;
use App\Support\Settings\IntegrationSettings;

/**
 * The SMTP configuration entered in the panel, its readiness, and the
 * runtime override.
 *
 * Precedence: whatever the environment defines wins. A server that sets
 * MAIL_HOST keeps control; the panel then only reports what the server is
 * using. Otherwise the (encrypted) panel settings are applied at runtime.
 *
 * "READY" never means "a host was typed in": the transport must be
 * enabled, complete, not the log/array mailer, and a lead notification
 * inbox must exist - otherwise a new lead still reaches nobody.
 */
class MailSettings
{
    public const ENCRYPTIONS = ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'بدون تشفير'];

    public function __construct(private readonly IntegrationSettings $settings) {}

    /**
     * The environment owns the transport only when it defines a real one.
     * Laravel's scaffold ships MAIL_MAILER=log with a placeholder
     * MAIL_HOST, which must not count as "the server handles mail" - that
     * would leave the panel unable to configure anything.
     */
    public function environmentControlsMail(): bool
    {
        return filled(env('MAIL_HOST')) && ! in_array((string) env('MAIL_MAILER'), ['', 'log', 'array'], true);
    }

    public function isEnabled(): bool
    {
        return $this->settings->bool(IntegrationSettings::SMTP_ENABLED);
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return [
            'host' => $this->settings->string(IntegrationSettings::SMTP_HOST),
            'port' => $this->settings->int(IntegrationSettings::SMTP_PORT, 587),
            'encryption' => $this->settings->string(IntegrationSettings::SMTP_ENCRYPTION) ?? 'tls',
            'username' => $this->settings->string(IntegrationSettings::SMTP_USERNAME),
            'from_address' => $this->settings->string(IntegrationSettings::SMTP_FROM_ADDRESS),
            'from_name' => $this->settings->string(IntegrationSettings::SMTP_FROM_NAME),
        ];
    }

    public function hasPassword(): bool
    {
        return $this->settings->hasSecret(IntegrationSettings::SMTP_PASSWORD);
    }

    public function notificationRecipient(): ?string
    {
        $recipient = BusinessProfile::query()->value('lead_notification_email');

        return filled($recipient) ? $recipient : null;
    }

    /**
     * Applies the stored settings to the mail configuration for this
     * process. Called from a service provider; does nothing when the
     * environment already defines the transport or the settings are off.
     */
    public function applyRuntimeConfiguration(): void
    {
        if ($this->environmentControlsMail() || ! $this->isEnabled()) {
            return;
        }

        $values = $this->values();

        if (blank($values['host']) || blank($values['from_address'])) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $values['host'],
            'mail.mailers.smtp.port' => $values['port'],
            'mail.mailers.smtp.username' => $values['username'],
            'mail.mailers.smtp.password' => $this->settings->revealSecret(IntegrationSettings::SMTP_PASSWORD),
            'mail.mailers.smtp.scheme' => $values['encryption'] === 'none' ? 'smtp' : 'smtps',
            'mail.mailers.smtp.encryption' => $values['encryption'] === 'none' ? null : $values['encryption'],
            'mail.from.address' => $values['from_address'],
            'mail.from.name' => $values['from_name'] ?? config('app.name'),
        ]);
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    public function readiness(): array
    {
        $mailer = (string) config('mail.default');
        $recipient = $this->notificationRecipient();

        if ($this->environmentControlsMail()) {
            return in_array($mailer, ['log', 'array'], true)
                ? ['state' => 'not_configured', 'label' => 'غير مهيأ', 'detail' => "البريد مضبوط في بيئة الخادم على «{$mailer}» — لا تُرسل رسائل فعليًا."]
                : ($recipient
                    ? ['state' => 'ready', 'label' => 'جاهز', 'detail' => "مضبوط في بيئة الخادم (mailer: {$mailer})، ويُرسل إلى {$recipient}."]
                    : ['state' => 'configured', 'label' => 'مهيأ جزئيًا', 'detail' => 'البريد مضبوط في بيئة الخادم، لكن بريد استقبال الطلبات فارغ في بيانات المنشأة.']);
        }

        $values = $this->values();
        $missing = collect([
            'الخادم (host)' => blank($values['host']),
            'المنفذ (port)' => $values['port'] < 1 || $values['port'] > 65535,
            'كلمة المرور' => ! $this->hasPassword(),
            'المرسِل (from)' => blank($values['from_address']),
            'بريد استقبال الطلبات' => blank($recipient),
        ])->filter()->keys();

        // "Nothing entered yet" is judged on the values a person types, not
        // on the defaulted port.
        $nothingEntered = blank($values['host']) && blank($values['username']) && blank($values['from_address']) && ! $this->hasPassword();

        if (! $this->isEnabled()) {
            return $nothingEntered
                ? ['state' => 'not_configured', 'label' => 'غير مهيأ', 'detail' => 'لم تُدخل إعدادات SMTP بعد. الطلبات تُحفظ في اللوحة ولا يُرسل بريد.']
                : ['state' => 'configured', 'label' => 'مهيأ لكنه متوقف', 'detail' => 'الإعدادات محفوظة لكن الإرسال غير مفعّل.'];
        }

        if ($missing->isNotEmpty()) {
            return ['state' => 'error', 'label' => 'ناقص', 'detail' => 'الإرسال مفعّل لكن ينقص: '.$missing->implode('، ').'.'];
        }

        return ['state' => 'ready', 'label' => 'جاهز', 'detail' => "يُرسل عبر {$values['host']} إلى {$recipient}."];
    }

    public function isReady(): bool
    {
        return $this->readiness()['state'] === 'ready';
    }
}
