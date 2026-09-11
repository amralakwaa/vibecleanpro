<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');

Route::get('/services/{slug}', [PublicPageController::class, 'service'])->name('public.service');
Route::get('/areas/{slug}', [PublicPageController::class, 'area'])->name('public.area');
Route::get('/projects/{slug}', [PublicPageController::class, 'project'])->name('public.project');
Route::get('/blog/{slug}', [PublicPageController::class, 'article'])->name('public.article');
Route::get('/offers/{slug}', [PublicPageController::class, 'offer'])->name('public.offer');

// Standalone pages (trust/legal/landing) live at the root, so this must be
// registered last and must never be able to swallow reserved top-level
// paths like the admin panel.
Route::get('/{slug}', [PublicPageController::class, 'standalone'])
    ->where('slug', '(?!admin$|sitemap\.xml$|robots\.txt$).*')
    ->name('public.standalone');
