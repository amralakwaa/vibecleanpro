<?php

namespace App\Seo;

use App\Enums\PageType;
use App\Models\Area;
use App\Models\Article;
use App\Models\BusinessProfile;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Service;
use App\Seo\Enums\CheckSeverity;
use App\Seo\ValueObjects\PublishingGateResult;
use App\Seo\ValueObjects\SeoCheckResult;

/**
 * Evaluates a Page against technical and editorial checks before it is
 * allowed to be Published + indexable. ERROR blocks publishing when the
 * problem is a genuine technical fault (would break a URL, leave the page
 * empty, or hand Google something malformed); WARNING is everything that
 * is a legitimate editorial judgment call an SEO Manager or Content
 * Manager should see but may reasonably publish through. We do not turn
 * every best practice into a blocking error.
 *
 * Area pages get extra checks (see checksForArea()) - this project's
 * "Local Page Quality Gate" - folded into the same pipeline rather than a
 * separate class, since it is the same ERROR/WARNING/PASS machinery
 * applied to a few more type-specific questions.
 */
class PublishingGate
{
    public function __construct(
        private readonly CanonicalResolver $canonical,
        private readonly InternalLinkAnalyzer $links,
        private readonly DuplicateSimilarityAnalyzer $similarity,
    ) {}

    public function evaluate(Page $page): PublishingGateResult
    {
        $checks = array_filter([
            $this->checkTitle($page),
            $this->checkSlug($page),
            $this->checkPageableExists($page),
            $this->checkCanonicalValid($page),
            $this->checkRobotsConsistency($page),
            $this->checkRedirectConflict($page),
            $this->checkAboutSingleton($page),
            $this->checkContentNotEmpty($page),
            $this->checkSeoTitle($page),
            $this->checkMetaDescription($page),
            $this->checkFeaturedImage($page),
            $this->checkImageAltText($page),
            $this->checkInternalLinks($page),
            $this->checkRelatedContent($page),
            $this->checkCallToAction($page),
            $this->checkArticleAuthor($page),
        ]);

        if ($page->type === PageType::Area && $page->pageable instanceof Area) {
            $checks = [...$checks, ...array_filter($this->checksForArea($page, $page->pageable))];
        }

        return new PublishingGateResult(array_values($checks));
    }

    private function checkTitle(Page $page): SeoCheckResult
    {
        return trim($page->title) === ''
            ? $this->error('title', 'العنوان مفقود.')
            : $this->pass('title', 'العنوان موجود.');
    }

    private function checkSlug(Page $page): SeoCheckResult
    {
        $valid = (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $page->slug ?? '');

        return $valid
            ? $this->pass('slug', 'الرابط (Slug) صالح.')
            : $this->error('slug', 'الرابط (Slug) غير صالح - يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطات فقط.');
    }

    private function checkPageableExists(Page $page): ?SeoCheckResult
    {
        $entityBacked = in_array($page->type, [PageType::Service, PageType::Area, PageType::Project, PageType::Article, PageType::Offer], true);

        if (! $entityBacked) {
            return null;
        }

        return $page->pageable
            ? $this->pass('pageable', 'الصفحة مرتبطة بالكيان الصحيح.')
            : $this->error('pageable', 'نوع الصفحة يتطلب ارتباطًا بكيان (خدمة/منطقة/مشروع...)، ولا يوجد ارتباط فعلي.');
    }

    private function checkCanonicalValid(Page $page): ?SeoCheckResult
    {
        $custom = $page->seoMetadata?->canonical_url;

        if (! $custom) {
            return $this->pass('canonical', 'لا يوجد Canonical مخصص - سيُستخدم رابط الصفحة نفسها.');
        }

        return $this->canonical->isValid($custom)
            ? $this->pass('canonical', 'Canonical المخصص صالح.')
            : $this->error('canonical', 'Canonical المخصص غير صالح (رابط تالف أو ناقص).');
    }

    private function checkRobotsConsistency(Page $page): ?SeoCheckResult
    {
        if ($page->seoMetadata?->robots_index === false) {
            return $this->warning('robots', 'الصفحة معدّة كـ noindex - لن تظهر في نتائج البحث حتى بعد النشر. تأكد أن هذا مقصود.');
        }

        return null;
    }

    private function checkRedirectConflict(Page $page): ?SeoCheckResult
    {
        $ownPath = app(UrlResolver::class)->pathForPage($page);

        $conflict = Redirect::query()->where('from_path', $ownPath)->where('is_active', true)->exists();

        return $conflict
            ? $this->error('redirect_conflict', 'يوجد تحويل (Redirect) نشط من نفس رابط هذه الصفحة، وهذا تعارض يجب حله أولًا.')
            : $this->pass('redirect_conflict', 'لا يوجد تعارض مع أي Redirect.');
    }

    /**
     * Exactly one published About page represents the company: a second
     * one would be a second identity competing for the same navigation
     * slot and the same entity signals. Drafts are fine (a replacement
     * can be prepared); publishing while another is live is an ERROR,
     * which PageObserver turns back into Draft on every save path.
     */
    private function checkAboutSingleton(Page $page): ?SeoCheckResult
    {
        if ($page->type !== PageType::About) {
            return null;
        }

        $other = Page::query()
            ->where('type', PageType::About)
            ->whereKeyNot($page->getKey())
            ->published()
            ->first();

        return $other
            ? $this->error('about_singleton', "توجد صفحة \"من نحن\" منشورة بالفعل ({$other->title}). لا يمكن نشر أكثر من صفحة هوية واحدة - أرجع الأخرى إلى مسودة أولًا.")
            : $this->pass('about_singleton', 'لا توجد صفحة "من نحن" منشورة أخرى.');
    }

    private function checkContentNotEmpty(Page $page): SeoCheckResult
    {
        if ($page->contentBlocks->isNotEmpty()) {
            return $this->pass('content_empty', 'يوجد محتوى فعلي في الصفحة.');
        }

        // The About page's body is the company identity entered on the
        // business profile (see pages/about.blade.php), so for that type
        // identity data counts as content - blocks are optional extras.
        if ($page->type === PageType::About && $this->hasCompanyIdentityContent()) {
            return $this->pass('content_empty', 'محتوى صفحة من نحن يأتي من هوية الشركة في بيانات المنشأة.');
        }

        return $page->type === PageType::About
            ? $this->error('content_empty', 'صفحة من نحن بلا محتوى: أضف بيان الهوية أو قصة الشركة في "بيانات المنشأة"، أو أضف أقسامًا للصفحة.')
            : $this->error('content_empty', 'الصفحة لا تحتوي على أي محتوى (أقسام فارغة).');
    }

    private function hasCompanyIdentityContent(): bool
    {
        $profile = BusinessProfile::query()->first();

        return $profile !== null && (filled($profile->identity_statement) || filled(trim(strip_tags((string) $profile->story))) || filled($profile->tagline));
    }

    private function checkSeoTitle(Page $page): SeoCheckResult
    {
        return blank($page->seoMetadata?->meta_title)
            ? $this->warning('seo_title', 'لا يوجد عنوان SEO مخصص - سيُستخدم عنوان الصفحة.')
            : $this->pass('seo_title', 'عنوان SEO موجود.');
    }

    private function checkMetaDescription(Page $page): SeoCheckResult
    {
        return blank($page->seoMetadata?->meta_description)
            ? $this->warning('meta_description', 'لا يوجد وصف تعريفي (Meta Description).')
            : $this->pass('meta_description', 'الوصف التعريفي موجود.');
    }

    private function checkFeaturedImage(Page $page): ?SeoCheckResult
    {
        $entity = $page->pageable;

        $hasImage = match (true) {
            $entity instanceof Service => (bool) $entity->featured_media_id,
            $entity instanceof Article => (bool) $entity->featured_media_id,
            $entity instanceof Offer => (bool) $entity->featured_media_id,
            $entity instanceof Project => $entity->media()->exists(),
            default => null,
        };

        if ($hasImage === null) {
            return null;
        }

        return $hasImage
            ? $this->pass('featured_image', 'توجد صورة رئيسية.')
            : $this->warning('featured_image', 'لا توجد صورة رئيسية لهذه الصفحة.');
    }

    private function checkImageAltText(Page $page): ?SeoCheckResult
    {
        $entity = $page->pageable;

        $media = match (true) {
            $entity instanceof Service => $entity->featuredMedia,
            $entity instanceof Article => $entity->featuredMedia,
            $entity instanceof Offer => $entity->featuredMedia,
            default => null,
        };

        if (! $media) {
            return null;
        }

        return blank($media->alt_text)
            ? $this->warning('image_alt', 'الصورة الرئيسية بلا نص بديل (Alt Text).')
            : $this->pass('image_alt', 'الصورة الرئيسية تحتوي نصًا بديلًا.');
    }

    private function checkInternalLinks(Page $page): SeoCheckResult
    {
        $count = $this->links->inboundCount($page);

        return $count === 0
            ? $this->warning('internal_links', 'لا توجد روابط داخلية واردة لهذه الصفحة (قد تصبح صفحة يتيمة).')
            : $this->pass('internal_links', "توجد {$count} إشارة ربط داخلي واردة.");
    }

    private function checkRelatedContent(Page $page): ?SeoCheckResult
    {
        $entity = $page->pageable;

        $relatedCount = match (true) {
            $entity instanceof Service => $entity->areas()->count() + $entity->projects()->count(),
            $entity instanceof Area => $entity->services()->count() + $entity->projects()->count(),
            $entity instanceof Article => $entity->services()->count() + $entity->areas()->count(),
            $entity instanceof Offer => $entity->services()->count() + $entity->areas()->count(),
            default => null,
        };

        if ($relatedCount === null) {
            return null;
        }

        return $relatedCount === 0
            ? $this->warning('related_content', 'لا يوجد محتوى مرتبط (خدمات/مناطق/مشاريع) بهذه الصفحة.')
            : $this->pass('related_content', 'يوجد محتوى مرتبط.');
    }

    private function checkCallToAction(Page $page): ?SeoCheckResult
    {
        $commercial = in_array($page->type, [PageType::Service, PageType::Offer, PageType::Area], true);

        if (! $commercial) {
            return null;
        }

        $hasCta = $page->contentBlocks->contains('type', 'cta');

        return $hasCta
            ? $this->pass('cta', 'توجد دعوة للعمل (CTA) واضحة.')
            : $this->warning('cta', 'لا توجد دعوة للعمل (CTA) في هذه الصفحة التجارية.');
    }

    private function checkArticleAuthor(Page $page): ?SeoCheckResult
    {
        if (! $page->pageable instanceof Article) {
            return null;
        }

        return $page->pageable->author_id
            ? $this->pass('article_author', 'المقال له كاتب محدد.')
            : $this->warning('article_author', 'لا يوجد كاتب محدد لهذا المقال.');
    }

    /**
     * @return array<int, ?SeoCheckResult>
     */
    private function checksForArea(Page $page, Area $area): array
    {
        $servicesCount = $area->services()->count();
        $projectsCount = $area->projects()->count();
        $textLength = $page->contentBlocks->sum(fn ($block) => isset($block->data['content']) && is_string($block->data['content'])
            ? mb_strlen(strip_tags($block->data['content']))
            : 0);

        $checks = [
            $servicesCount === 0
                ? $this->warning('local_services', 'المنطقة غير مرتبطة بأي خدمة متاحة فيها.')
                : $this->pass('local_services', "مرتبطة بـ{$servicesCount} خدمة."),
            $projectsCount === 0
                ? $this->warning('local_projects', 'لا يوجد مشروع فعلي موثّق في هذه المنطقة - دليل محلي حقيقي يقوّي الصفحة.')
                : $this->pass('local_projects', "يوجد {$projectsCount} مشروع موثّق."),
            $textLength < 150
                ? $this->warning('local_content_depth', 'محتوى المنطقة قصير جدًا - قد لا يقدّم قيمة مستقلة كافية للزائر.')
                : $this->pass('local_content_depth', 'محتوى المنطقة له عمق كافٍ.'),
        ];

        $similar = $this->similarity->findSimilarAreaPages()
            ->first(fn ($pair) => $pair->pageA->id === $page->id || $pair->pageB->id === $page->id);

        $checks[] = $similar
            ? $this->warning('local_similarity', sprintf(
                'هذه الصفحة متشابهة جدًا (%d%%) مع صفحة منطقة أخرى: %s.',
                round($similar->score * 100),
                ($similar->pageA->id === $page->id ? $similar->pageB : $similar->pageA)->title,
            ))
            : $this->pass('local_similarity', 'لا يوجد تشابه كبير مع صفحات مناطق أخرى.');

        return $checks;
    }

    private function error(string $key, string $message): SeoCheckResult
    {
        return new SeoCheckResult($key, CheckSeverity::Error, $message);
    }

    private function warning(string $key, string $message): SeoCheckResult
    {
        return new SeoCheckResult($key, CheckSeverity::Warning, $message);
    }

    private function pass(string $key, string $message): SeoCheckResult
    {
        return new SeoCheckResult($key, CheckSeverity::Pass, $message);
    }
}
