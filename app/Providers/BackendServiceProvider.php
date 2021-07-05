<?php

namespace App\Providers;

use App\Interfaces\AchievementInterface;
use App\Interfaces\ArticleInterface;
use App\Interfaces\BaseInterface;
use App\Interfaces\CategoryInterface;
use App\Interfaces\CityInterface;
use App\Interfaces\CountryInterface;
use App\Interfaces\DisputeInterface;
use App\Interfaces\EducationInterface;
use App\Interfaces\ExperienceInterface;
use App\Interfaces\MediaInterface;
use App\Interfaces\PortfolioInterface;
use App\Interfaces\PostInterface;
use App\Interfaces\ProjectInterface;
use App\Interfaces\ProjectRequestInterface;
use App\Interfaces\PropertyInterface;
use App\Interfaces\RequestPackageInterface;
use App\Interfaces\SettingInterface;
use App\Interfaces\SkillInterface;
use App\Interfaces\StateInterface;
use App\Interfaces\StoryInterface;
use App\Interfaces\TagInterface;
use App\Interfaces\UserInterface;
use App\Models\Achievement;
use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Dispute;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Media;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Project;
use App\Models\ProjectProperty;
use App\Models\Request;
use App\Models\RequestPackage;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\State;
use App\Models\Story;
use App\Models\Tag;
use App\Models\User;
use App\Repositories\AchievementRepository;
use App\Repositories\ArticleRepository;
use App\Repositories\BaseRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CityRepository;
use App\Repositories\CountryRepository;
use App\Repositories\DisputeRepository;
use App\Repositories\EducationRepository;
use App\Repositories\ExperienceRepository;
use App\Repositories\MediaRepository;
use App\Repositories\PortfolioRepository;
use App\Repositories\PostRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ProjectRequestRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\RequestPackageRepository;
use App\Repositories\SettingRepository;
use App\Repositories\SkillRepository;
use App\Repositories\StateRepository;
use App\Repositories\StoryRepository;
use App\Repositories\TagRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class BackendServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            BaseInterface::class,
            BaseRepository::class
        );

        $this->app->bind(
            UserInterface::class,
            function() {
                return new UserRepository(new User);
            }
        );

        $this->app->bind(
            CategoryInterface::class,
            function() {
                return new CategoryRepository(new Category);
            }
        );

        $this->app->bind(
            ArticleInterface::class,
            function() {
                return new ArticleRepository(new Article);
            }
        );

        $this->app->bind(
            TagInterface::class,
            function() {
                return new TagRepository(new Tag);
            }
        );

        $this->app->bind(
            MediaInterface::class,
            function() {
                return new MediaRepository(new Media);
            }
        );

        $this->app->bind(
            PostInterface::class,
            function() {
                return new PostRepository(new Post);
            }
        );

        $this->app->bind(
            StoryInterface::class,
            function() {
                return new StoryRepository(new Story);
            }
        );

        $this->app->bind(
            ExperienceInterface::class,
            function() {
                return new ExperienceRepository(new Experience);
            }
        );

        $this->app->bind(
            AchievementInterface::class,
            function() {
                return new AchievementRepository(new Achievement);
            }
        );

        $this->app->bind(
            EducationInterface::class,
            function() {
                return new EducationRepository(new Education);
            }
        );

        $this->app->bind(
            SkillInterface::class,
            function() {
                return new SkillRepository(new Skill);
            }
        );
        $this->app->bind(
            ProjectInterface::class,
            function() {
                return new ProjectRepository(new Project);
            }
        );
        $this->app->bind(
            PropertyInterface::class,
            function() {
                return new PropertyRepository(new ProjectProperty);
            }
        );
        $this->app->bind(
            ProjectRequestInterface::class,
            function() {
                return new ProjectRequestRepository(new Request);
            }
        );
        $this->app->bind(
            PortfolioInterface::class,
            function() {
                return new PortfolioRepository(new Portfolio);
            }
        );
        $this->app->bind(
            SettingInterface::class,
            function() {
                return new SettingRepository(new Setting);
            }
        );
        $this->app->bind(
            RequestPackageInterface::class,
            function() {
                return new RequestPackageRepository(new RequestPackage);
            }
        );
        $this->app->bind(
            DisputeInterface::class,
            function() {
                return new DisputeRepository(new Dispute);
            }
        );
        $this->app->bind(
            CountryInterface::class,
            function() {
                return new CountryRepository(new Country);
            }
        );
        $this->app->bind(
            CityInterface::class,
            function() {
                return new CityRepository(new City);
            }
        );
        $this->app->bind(
            StateInterface::class,
            function() {
                return new StateRepository(new State);
            }
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
