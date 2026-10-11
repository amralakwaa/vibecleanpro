<?php

namespace App\Http\Middleware;

use App\Support\Scheduling\BackgroundHeartbeat;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns visitor traffic into the cron this hosting account does not have
 * (see App\Support\Scheduling\BackgroundHeartbeat for why).
 *
 * The work is started from terminate(), which Laravel calls after the
 * response has been sent, and the heartbeat only ever spawns a detached
 * process - so nothing here is on the path of the page the visitor is
 * waiting for, and the request that happens to win the once-a-minute lock
 * is not punished for it.
 */
class TriggerBackgroundWork
{
    public function __construct(private readonly BackgroundHeartbeat $heartbeat) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->heartbeat->tick();
    }
}
