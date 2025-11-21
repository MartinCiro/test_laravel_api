<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Users
use Core\Users\Ports\UserRepositoryInterface;
use Core\Users\Ports\UserServiceInterface;
use Infrastructure\Persistence\Eloquent\Repositories\UserRepository;
use Core\Users\Application\Services\UserService;

// Projects
use Core\Projects\Ports\ProjectRepositoryInterface;
use Core\Projects\Ports\ProjectServiceInterface;
use Infrastructure\Persistence\Eloquent\Repositories\ProjectRepository;
use Core\Projects\Application\Services\ProjectService;

// Tasks
use Core\Tasks\Ports\TaskRepositoryInterface;
use Core\Tasks\Ports\TaskServiceInterface;
use Infrastructure\Persistence\Eloquent\Repositories\TaskRepository;
use Core\Tasks\Application\Services\TaskService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ==================== USERS ====================
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
        );

        // ==================== PROJECTS ====================
        $this->app->bind(
            ProjectRepositoryInterface::class,
            ProjectRepository::class
        );

        $this->app->bind(
            ProjectServiceInterface::class,
            ProjectService::class
        );

        // ==================== TASKS ====================
        $this->app->bind(
            TaskRepositoryInterface::class,
            TaskRepository::class
        );

        $this->app->bind(
            TaskServiceInterface::class,
            TaskService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}