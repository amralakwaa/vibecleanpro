<?php

namespace Tests\Feature\Seo;

use App\Seo\BreadcrumbResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_resolves_home_followed_by_the_current_page(): void
    {
        $page = $this->createCompliantServicePage();

        $items = app(BreadcrumbResolver::class)->resolve($page);

        $this->assertCount(2, $items);
        $this->assertSame('الرئيسية', $items[0]->label);
        $this->assertSame('/', $items[0]->url);
        $this->assertSame($page->title, $items[1]->label);
    }

    public function test_the_current_page_crumb_carries_no_url_since_it_must_not_link_to_itself(): void
    {
        $page = $this->createCompliantServicePage();

        $items = app(BreadcrumbResolver::class)->resolve($page);

        $this->assertNull($items[1]->url);
    }

    public function test_rendered_on_the_public_page_the_current_crumb_is_plain_text_not_a_link(): void
    {
        $page = $this->createCompliantServicePage(slug: 'breadcrumb-check');

        $response = $this->get('/services/breadcrumb-check');

        $response->assertOk();
        $response->assertSee('<a href="/">الرئيسية</a>', false);
        $response->assertSee('<span>'.$page->title.'</span>', false);
    }
}
