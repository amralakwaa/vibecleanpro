<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Records sign-in events in the existing audit_logs table: who (user id,
 * or the attempted email when no account matched), what, and from which
 * IP. Never a password, token or two-factor code - credential payloads are
 * read only for their email field.
 */
class LogSecurityEvent
{
    public function handleLogin(Login $event): void
    {
        self::record('auth.login', $event->user);
    }

    public function handleLogout(Logout $event): void
    {
        self::record('auth.logout', $event->user);
    }

    public function handleFailed(Failed $event): void
    {
        self::record('auth.login_failed', $event->user, $event->credentials['email'] ?? null);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        self::record('auth.password_reset', $event->user);
    }

    public static function record(string $action, ?Authenticatable $user, mixed $attemptedEmail = null): void
    {
        AuditLog::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'auditable_type' => User::class,
            'auditable_id' => (int) ($user?->getAuthIdentifier() ?? 0),
            'changes' => is_string($attemptedEmail) ? ['attempted_email' => mb_substr($attemptedEmail, 0, 255)] : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
