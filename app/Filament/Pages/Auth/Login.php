<?php

namespace App\Filament\Pages\Auth;

use App\Listeners\LogSecurityEvent;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;

/**
 * Filament's login already allows 5 attempts per minute per IP; this only
 * records the moment that limit is hit in the audit log.
 */
class Login extends BaseLogin
{
    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        LogSecurityEvent::record('auth.login_rate_limited', null, $this->data['email'] ?? null);

        return parent::getRateLimitedNotification($exception);
    }
}
