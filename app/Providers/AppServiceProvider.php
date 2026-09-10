<?php

namespace App\Providers;

use App\Models\Area;
use App\Models\Article;
use App\Models\Offer;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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

        // Super Admin bypasses every Policy/Gate check outright.
        Gate::before(fn ($user, string $ability) => $user->hasRole('Super Admin') ? true : null);
    }
}
