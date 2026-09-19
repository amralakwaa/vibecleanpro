<?php

namespace App\Observers;

use App\Enums\MediaPrivacyStatus;
use App\Enums\MediaStatus;
use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use App\Support\Media\ResponsiveImageGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MediaObserver
{
    /**
     * The rule lives in the model layer, not only in the admin form, so an
     * import script or a tinker session cannot mark an unverified photo
     * ready either.
     */
    public function saving(Media $media): void
    {
        $this->ensureApprover($media);

        if ($media->privacy_status instanceof MediaPrivacyStatus && $media->privacy_status->isHeld()) {
            $media->status = MediaStatus::Private;

            return;
        }

        if ($media->status === MediaStatus::Ready && ($problem = $media->readinessProblem())) {
            throw ValidationException::withMessages(['status' => $problem]);
        }
    }

    /**
     * A new or replaced file gets its responsive sizes in the background.
     */
    public function saved(Media $media): void
    {
        if ($media->wasRecentlyCreated || $media->wasChanged('path')) {
            GenerateMediaVariants::dispatch($media)->afterCommit();
        }
    }

    public function deleted(Media $media): void
    {
        app(ResponsiveImageGenerator::class)->delete($media);
    }

    /**
     * Clearing a photo's privacy or approving it for publishing is a
     * decision for someone holding approve_media (Media Manager,
     * Administrator). Any editor may upload; the upload simply waits as
     * pending. Console runs (the library import) have no user and are
     * trusted.
     */
    private function ensureApprover(Media $media): void
    {
        $user = Auth::user();

        if (! $user || $user->can('approve_media')) {
            return;
        }

        $approvesPublishing = $media->isDirty('status') && $media->status === MediaStatus::Ready;
        $clearsPrivacy = $media->isDirty('privacy_status') && $media->privacy_status === MediaPrivacyStatus::Cleared;

        if ($approvesPublishing || $clearsPrivacy) {
            throw ValidationException::withMessages(['status' => 'اعتماد الصورة أو مراجعة خصوصيتها من صلاحية مدير الوسائط.']);
        }
    }
}
