<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * content_blocks is stored as ordered rows in its own table, but edited
 * through Filament's Builder field, which natively works with a single JSON
 * array. EditPage translates between the two by hand (see its
 * mutateFormDataBeforeFill/afterSave) - that translation is what these
 * tests exercise, not Filament's Builder component itself.
 *
 * Builder::fake() is Filament's documented test helper that swaps the
 * component's internal UUID item-tracking for plain sequential keys, so a
 * test doesn't need to guess real UUIDs to address an item.
 */
class PageContentBlocksFormTest extends TestCase
{
    use RefreshDatabase;

    private $undoBuilderFake;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($admin);

        $this->undoBuilderFake = Builder::fake();
    }

    protected function tearDown(): void
    {
        ($this->undoBuilderFake)();

        parent::tearDown();
    }

    public function test_saving_the_builder_creates_content_block_rows_in_order(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal]);

        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->fillForm([
                'content_blocks_builder' => [
                    ['type' => 'rich_text', 'data' => ['content' => '<p>أولًا</p>']],
                    ['type' => 'cta', 'data' => ['heading' => 'تواصل معنا', 'button_label' => 'اتصل الآن', 'button_url' => 'https://example.com']],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $blocks = $page->contentBlocks()->orderBy('position')->get();

        $this->assertCount(2, $blocks);
        $this->assertSame('rich_text', $blocks[0]->type);
        $this->assertSame(0, $blocks[0]->position);
        $this->assertSame('<p>أولًا</p>', $blocks[0]->data['content']);
        $this->assertSame('cta', $blocks[1]->type);
        $this->assertSame(1, $blocks[1]->position);
    }

    public function test_reordering_blocks_updates_their_stored_position(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal]);
        $first = $page->contentBlocks()->create(['type' => 'rich_text', 'data' => ['content' => 'A'], 'position' => 0]);
        $second = $page->contentBlocks()->create(['type' => 'cta', 'data' => ['heading' => 'B', 'button_label' => 'B', 'button_url' => 'https://example.com'], 'position' => 1]);

        // Submit the same two blocks in reversed order, as the Builder does
        // when an editor drags an item above another.
        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->fillForm([
                'content_blocks_builder' => [
                    ['type' => $second->type, 'data' => $second->data],
                    ['type' => $first->type, 'data' => $first->data],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $blocks = $page->contentBlocks()->orderBy('position')->get();

        $this->assertCount(2, $blocks);
        $this->assertSame('cta', $blocks[0]->type);
        $this->assertSame(0, $blocks[0]->position);
        $this->assertSame('rich_text', $blocks[1]->type);
        $this->assertSame(1, $blocks[1]->position);
    }

    public function test_editing_a_blocks_content_updates_it_without_creating_a_duplicate(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal]);
        $page->contentBlocks()->create(['type' => 'rich_text', 'data' => ['content' => 'قديم'], 'position' => 0]);

        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->fillForm([
                'content_blocks_builder' => [
                    ['type' => 'rich_text', 'data' => ['content' => '<p>محدَّث</p>']],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $blocks = $page->contentBlocks()->get();

        $this->assertCount(1, $blocks, 'Editing a block created a duplicate instead of replacing it.');
        $this->assertSame('<p>محدَّث</p>', $blocks[0]->data['content']);
    }

    public function test_removing_a_block_from_the_builder_deletes_it(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal]);
        $page->contentBlocks()->create(['type' => 'rich_text', 'data' => ['content' => 'يبقى'], 'position' => 0]);
        $page->contentBlocks()->create(['type' => 'rich_text', 'data' => ['content' => 'يُحذف'], 'position' => 1]);

        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->fillForm([
                'content_blocks_builder' => [
                    ['type' => 'rich_text', 'data' => ['content' => '<p>يبقى</p>']],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $blocks = $page->contentBlocks()->get();

        $this->assertCount(1, $blocks);
        $this->assertSame('<p>يبقى</p>', $blocks[0]->data['content']);
    }

    public function test_saving_again_with_no_changes_does_not_accumulate_rows(): void
    {
        $page = Page::factory()->create(['type' => PageType::Legal]);
        $page->contentBlocks()->create(['type' => 'rich_text', 'data' => ['content' => 'ثابت'], 'position' => 0]);

        for ($i = 0; $i < 3; $i++) {
            Livewire::test(EditPage::class, ['record' => $page->getKey()])
                ->fillForm([
                    'content_blocks_builder' => [
                        ['type' => 'rich_text', 'data' => ['content' => 'ثابت']],
                    ],
                ])
                ->call('save')
                ->assertHasNoFormErrors();
        }

        $this->assertSame(1, $page->contentBlocks()->count(), 'Repeated saves duplicated the block instead of replacing it.');
    }
}
