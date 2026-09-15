<?php

namespace App\Providers;

use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Faq;
use App\Models\InternalLink;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\AreaGroupPolicy;
use App\Policies\AreaPolicy;
use App\Policies\ArticleCategoryPolicy;
use App\Policies\ArticlePolicy;
use App\Policies\FaqPolicy;
use App\Policies\InternalLinkPolicy;
use App\Policies\LeadPolicy;
use App\Policies\MediaPolicy;
use App\Policies\OfferPolicy;
use App\Policies\PagePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RedirectPolicy;
use App\Policies\RolePolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\ServicePolicy;
use App\Policies\TeamMemberPolicy;
use App\Policies\TestimonialPolicy;
use App\Policies\UserPolicy;
use App\Seo\DuplicateSimilarityAnalyzer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Model => Policy map. Registered explicitly (rather than relying on
     * Laravel's App\Models-only auto-discovery) so it stays correct even
     * for Role, which lives outside our namespace.
     */
    private const POLICIES = [
        Page::class => PagePolicy::class,
        Service::class => ServicePolicy::class,
        ServiceCategory::class => ServiceCategoryPolicy::class,
        Area::class => AreaPolicy::class,
        AreaGroup::class => AreaGroupPolicy::class,
        Project::class => ProjectPolicy::class,
        Article::class => ArticlePolicy::class,
        ArticleCategory::class => ArticleCategoryPolicy::class,
        Offer::class => OfferPolicy::class,
        Faq::class => FaqPolicy::class,
        InternalLink::class => InternalLinkPolicy::class,
        Testimonial::class => TestimonialPolicy::class,
        TeamMember::class => TeamMemberPolicy::class,
        Media::class => MediaPolicy::class,
        Lead::class => LeadPolicy::class,
        Redirect::class => RedirectPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bound as a singleton so its in-request memoization actually works:
        // PublishingGate resolves a similarity check per Area page it
        // evaluates, and the SEO Dashboard evaluates every published page,
        // so without a shared instance the O(n^2) scan would re-run once
        // per page in the same request.
        $this->app->singleton(DuplicateSimilarityAnalyzer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Stable, short aliases stored in pages.pageable_type instead of full
        // class names, so renaming/reorganizing a model's namespace later
        // never breaks existing polymorphic data.
        // 'user' is required here too: Spatie's model_has_roles/model_has_permissions
        // pivots are polymorphic against the User model, and enforceMorphMap()
        // rejects any unmapped class app-wide, not just our own pageable relation.
        Relation::enforceMorphMap([
            'service' => Service::class,
            'area' => Area::class,
            'project' => Project::class,
            'article' => Article::class,
            'offer' => Offer::class,
            'user' => User::class,
        ]);

        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // BusinessProfile and SiteSettings are singleton settings screens, not
        // list-based resources: each is gated by its own manage_* permission,
        // checked directly via $user->can() in the page class - no Policy or
        // Gate::define needed for a single-record settings screen.

        // Super Admin bypasses every Policy/Gate check outright.
        Gate::before(fn ($user, string $ability) => $user->hasRole('Super Admin') ? true : null);
    }
}
