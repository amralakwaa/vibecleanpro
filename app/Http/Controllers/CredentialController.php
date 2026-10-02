<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Seo\UrlResolver;
use App\Seo\ValueObjects\BreadcrumbItem;
use App\Seo\ValueObjects\SeoHeadData;
use Illuminate\Http\Response;

/**
 * Public-facing credential pages. A document code (VCP-QMS-001) resolves to the
 * full internal-standard document, which doubles as its verification page:
 * anyone with the code - including from a printed copy's QR - can confirm the
 * document, its version and status, issued by Vibe Clean Pro.
 *
 * Only publicly-visible credentials resolve (scopePublic): the roadmap
 * (planned/pending external ISO/licences) is never reachable here.
 */
class CredentialController extends Controller
{
    public function __construct(private readonly UrlResolver $urlResolver) {}

    public function verify(string $code): Response
    {
        $credential = Credential::query()
            ->public()
            ->where('document_code', $code)
            ->first();

        abort_if($credential === null, 404);

        $title = $credential->name_ar.' — '.$credential->document_code.' | فايب كلين برو';

        $seo = new SeoHeadData(
            title: $title,
            metaDescription: $credential->summary_ar,
            canonicalUrl: $this->urlResolver->absoluteUrl('/trust/verify/'.$credential->document_code),
            robotsContent: 'index, follow',
            openGraph: [
                'title' => $title,
                'description' => $credential->summary_ar,
                'image' => null,
                'url' => $this->urlResolver->absoluteUrl('/trust/verify/'.$credential->document_code),
                'type' => 'article',
            ],
            structuredData: [[
                '@context' => 'https://schema.org',
                '@type' => 'CreativeWork',
                'name' => $credential->name_ar,
                'identifier' => $credential->document_code,
                'version' => $credential->version,
                'inLanguage' => 'ar',
                'creator' => ['@type' => 'Organization', 'name' => 'Vibe Clean Pro'],
                'about' => $credential->summary_ar,
            ]],
            breadcrumbs: [
                new BreadcrumbItem('الرئيسية', '/'),
                new BreadcrumbItem('مركز الثقة', '/trust'),
                new BreadcrumbItem($credential->name_ar, null),
            ],
        );

        return response()->view('pages.credential-verify', [
            'seo' => $seo,
            'credential' => $credential,
        ]);
    }
}
