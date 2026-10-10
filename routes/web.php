<?php

use App\Http\Controllers\AreasIndexController;
use App\Http\Controllers\BlogIndexController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\OffersIndexController;
use App\Http\Controllers\ProjectsIndexController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\ServicesIndexController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TrackEventController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
Route::get('/llms.txt', [LlmsTxtController::class, 'index'])->name('llms');

Route::get('/services', [ServicesIndexController::class, 'index'])->name('public.services.index');
Route::get('/services/{slug}', [PublicPageController::class, 'service'])->name('public.service');

Route::get('/areas', [AreasIndexController::class, 'index'])->name('public.areas.index');
Route::get('/areas/{slug}', [PublicPageController::class, 'area'])->name('public.area');

Route::get('/projects', [ProjectsIndexController::class, 'index'])->name('public.projects.index');
Route::get('/projects/{slug}', [PublicPageController::class, 'project'])->name('public.project');

Route::get('/blog', [BlogIndexController::class, 'index'])->name('public.blog.index');
Route::get('/blog/{slug}', [PublicPageController::class, 'article'])->name('public.article');

Route::get('/offers', [OffersIndexController::class, 'index'])->name('public.offers.index');
Route::get('/offers/{slug}', [PublicPageController::class, 'offer'])->name('public.offer');

// The lead forms are throttled per IP, and Saudi mobile carriers put many
// subscribers behind one carrier-grade NAT address - so a per-IP limit is
// really a per-neighbourhood limit, and a tight one silently rejects real
// customers during a campaign. 6/min was tight enough to do that, so the
// limit is set where a crude flood still fails but a street of shoppers on
// the same mobile network does not. Spam is not what this defends against:
// StoreLeadRequest's honeypot does that, and it catches bots at any rate.
Route::get('/contact', [ContactController::class, 'index'])->name('public.contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:30,1')->name('public.contact.store');

Route::get('/quote', [QuoteController::class, 'create'])->name('public.quote');
Route::post('/quote', [QuoteController::class, 'store'])->middleware('throttle:30,1')->name('public.quote.store');

// Internal-standard document / verification page. Registered before the
// standalone catch-all; a document code (VCP-QMS-001) resolves to the full
// standard, issued by Vibe Clean Pro, and confirms its version and status.
Route::get('/trust/verify/{code}', [CredentialController::class, 'verify'])
    ->where('code', '[A-Za-z0-9-]+')
    ->name('public.trust.verify');

Route::post('/e', TrackEventController::class)->middleware('throttle:30,1')->name('public.track');

// Standalone pages (trust/legal/landing - including editor-managed pages
// like /about, /faq, /service-guarantee once created in the admin) live
// at the root, so this must be registered last and must never be able to
// swallow a reserved top-level path.
Route::get('/{slug}', [PublicPageController::class, 'standalone'])
    ->where('slug', '(?!admin$|sitemap\.xml$|robots\.txt$|llms\.txt$|services$|areas$|projects$|blog$|offers$|contact$|quote$).*')
    ->name('public.standalone');
