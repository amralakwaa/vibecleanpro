<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackEventRequest;
use App\Support\Tracking\ConversionRecorder;
use Illuminate\Http\Response;

/**
 * POST /e - receives the navigator.sendBeacon() calls from
 * resources/js/tracking.js for WhatsApp/phone clicks and quote-form starts.
 */
class TrackEventController extends Controller
{
    public function __invoke(TrackEventRequest $request, ConversionRecorder $recorder): Response
    {
        $recorder->recordFromBrowser($request->eventType(), $request->validated('path'), $request);

        return response()->noContent();
    }
}
