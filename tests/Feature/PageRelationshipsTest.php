<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\Area;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_have_a_page_via_polymorphic_relation(): void
    {
        $service = Service::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Service]);

        $service->page()->save($page);

        $this->assertTrue($service->page->is($page));
        $this->assertTrue($page->pageable->is($service));
        $this->assertSame(PageType::Service, $page->refresh()->type);
    }

    public function test_area_can_exist_without_a_page(): void
    {
        $area = Area::factory()->create();

        $this->assertNull($area->page);
        $this->assertDatabaseCount('pages', 0);
    }

    public function test_deleting_service_soft_deletes_its_page(): void
    {
        $service = Service::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Service]);
        $service->page()->save($page);

        $service->delete();

        $this->assertSoftDeleted('services', ['id' => $service->id]);
        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    }

    public function test_force_deleting_service_force_deletes_its_page(): void
    {
        $service = Service::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Service]);
        $service->page()->save($page);

        $service->forceDelete();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_restoring_service_restores_its_page(): void
    {
        $service = Service::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Service]);
        $service->page()->save($page);

        $service->delete();
        $service->restore();

        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'deleted_at' => null]);
    }

    public function test_restoring_area_restores_its_page_too(): void
    {
        $area = Area::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Area]);
        $area->page()->save($page);

        $area->delete();
        $area->restore();

        $this->assertDatabaseHas('areas', ['id' => $area->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'deleted_at' => null]);
    }

    public function test_an_entity_cannot_have_a_second_page(): void
    {
        $service = Service::factory()->create();
        $service->page()->save(Page::factory()->create(['type' => PageType::Service]));

        $this->expectException(QueryException::class);

        $service->page()->save(Page::factory()->make(['type' => PageType::Service]));
    }

    public function test_pageable_type_is_stored_using_the_morph_map_alias(): void
    {
        $service = Service::factory()->create();
        $page = Page::factory()->create(['type' => PageType::Service]);
        $service->page()->save($page);

        $this->assertSame('service', DB::table('pages')->where('id', $page->id)->value('pageable_type'));
    }
}
