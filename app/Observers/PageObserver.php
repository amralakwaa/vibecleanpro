<?php

namespace App\Observers;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Seo\Actions\RecordSlugChange;
use App\Seo\PublishingGate;
use Filament\Notifications\Notification;

class PageObserver
{
    public function updating(Page $page): void
    {
        if (! $page->isDirty('slug')) {
            return;
        }

        $oldSlug = $page->getOriginal('slug');

        if (! $oldSlug) {
            return;
        }

        app(RecordSlugChange::class)->handle($page, $oldSlug, $page->slug);
    }

    /**
     * The Publishing Gate is enforced here, once, rather than duplicated in
     * every Filament page that can set a Page to Published (the dedicated
     * Page resource, and the nested "page" section on Service/Area/
     * Project/Article/Offer resources) - every save path ends up writing
     * this same model, so this is the one place that always runs.
     *
     * A gate ERROR does not reject the whole save (the editor's other
     * changes are real and already committed); it safely reverts just the
     * status back to Draft and explains why, rather than leaving a
     * technically-broken page live and indexable.
     */
    public function saved(Page $page): void
    {
        if ($page->status !== PageStatus::Published) {
            return;
        }

        $fresh = $page->fresh(['contentBlocks', 'seoMetadata', 'pageable']);

        if (! $fresh) {
            return;
        }

        $result = app(PublishingGate::class)->evaluate($fresh);

        if ($result->canPublish()) {
            return;
        }

        $fresh->status = PageStatus::Draft;
        $fresh->saveQuietly();

        $errors = $result->errors()->pluck('message')->implode("\n");

        Notification::make()
            ->danger()
            ->title('تعذّر نشر الصفحة')
            ->body("تم إرجاع الصفحة \"{$fresh->title}\" إلى مسودة بسبب أخطاء تقنية:\n{$errors}")
            ->persistent()
            ->send();
    }
}
