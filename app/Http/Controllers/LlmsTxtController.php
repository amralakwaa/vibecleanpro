<?php

namespace App\Http\Controllers;

use App\Seo\LlmsTxtGenerator;
use Illuminate\Http\Response;

/**
 * Serves /llms.txt (llmstxt.org): a Markdown map of the site for large
 * language models and answer engines. Plain text/markdown, UTF-8, so a
 * crawler reads it as-is.
 */
class LlmsTxtController extends Controller
{
    public function index(LlmsTxtGenerator $generator): Response
    {
        return response($generator->generate(), 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8');
    }
}
