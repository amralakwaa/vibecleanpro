<?php

namespace Tests\Feature\Seo;

use App\Enums\PageType;
use App\Seo\BreadcrumbResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_a_service_resolves_home_then_the_services_index_then_the_page(): void
    {
        $page = $this->createCompliantServicePage();

        $items = app(BreadcrumbResolver::class)->resolve($page);

        $this->assertCount(3, $items);
        $this->assertSame(['الرئيسية', '/'], [$items[0]->label, $items[0]->url]);
        $this->assertSame(['خدماتنا', '/services'], [$items[1]->label, $items[1]->url]);
        $this->assertSame($page->title, $items[2]->label);
    }

    public function test_an_article_sits_under_the_blog_index(): void
    {
        $page = $this->makeBarePage(['type' => PageType::Article, 'slug' => 'some-guide']);

        $items = app(BreadcrumbResolver::class)->resolve($page);

        $this->assertSame(['المدونة', '/blog'], [$items[1]->label, $items[1]->url]);
    }

    public function test_a_page_type_without_an_index_keeps_a_two_level_trail(): void
    {
        $page = $this->makeBarePage(['type' => PageType::Legal, 'slug' => 'privacy']);

        $items = app(BreadcrumbResolver::class)->resolve($page);

        $this->assertCount(2, $items);
        $this->assertSame($page->title, $items[1]->label);
    }

    public function test_the_current_page_crumb_carries_no_url_since_it_must_not_link_to_itself(): void
    {
        $page = $this->createCompliantServicePage();

        $items = app(BreadcrumbResolver::class)->resolve($page);

        $this->assertNull(end($items)->url);
    }

    public function test_rendered_on_the_public_page_the_current_crumb_is_plain_text_not_a_link(): void
    {
        $page = $this->createCompliantServicePage(slug: 'breadcrumb-check');

        $response = $this->get('/services/breadcrumb-check');
        $response->assertOk();
        $html = $response->getContent();

        // Home renders as a real link to "/" ...
        $this->assertMatchesRegularExpression('#<a[^>]*href="/"[^>]*>\s*الرئيسية\s*</a>#u', $html);

        // ... but the current page never links to itself, and is marked
        // aria-current="page" instead of being a clickable element.
        $this->assertMatchesRegularExpression('#<span[^>]*aria-current="page"[^>]*>\s*'.preg_quote($page->title, '#').'\s*</span>#u', $html);
        $this->assertDoesNotMatchRegularExpression('#<a[^>]*>\s*'.preg_quote($page->title, '#').'\s*</a>#u', $html);
    }
}
