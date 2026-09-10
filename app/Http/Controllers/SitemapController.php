<?php

namespace App\Http\Controllers;

use App\Seo\SitemapGenerator;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(SitemapGenerator $sitemap): Response
    {
        $xml = view('seo.sitemap', ['entries' => $sitemap->entries()])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
