<?php

use App\Http\Controllers\AreasIndexController;
use App\Http\Controllers\BlogIndexController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
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

Route::get('/contact', [ContactController::class, 'index'])->name('public.contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:6,1')->name('public.contact.store');

Route::get('/quote', [QuoteController::class, 'create'])->name('public.quote');
Route::post('/quote', [QuoteController::class, 'store'])->middleware('throttle:6,1')->name('public.quote.store');

Route::post('/e', TrackEventController::class)->middleware('throttle:30,1')->name('public.track');

// Standalone pages (trust/legal/landing - including editor-managed pages
// like /about, /faq, /service-guarantee once created in the admin) live
// at the root, so this must be registered last and must never be able to
// swallow a reserved top-level path.
Route::get('/{slug}', [PublicPageController::class, 'standalone'])
    ->where('slug', '(?!admin$|sitemap\.xml$|robots\.txt$|services$|areas$|projects$|blog$|offers$|contact$|quote$).*')
    ->name('public.standalone');
