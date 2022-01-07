<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\Observers\ProfileObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Relation::morphMap([
            'post' => Post::class,
            'story' => Story::class,
            'article' => Article::class,
            'portfolio' => Portfolio::class,
            'profile' => Profile::class,
            'project' => Project::class,
            'user' => User::class,
        ]);
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        User::observe(UserObserver::class);
        Profile::observe(ProfileObserver::class);
    }
}
