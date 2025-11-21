<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Users\Ports\UserRepositoryInterface;
use Core\Users\Ports\UserServiceInterface;
use Infrastructure\Persistence\Eloquent\Repositories\UserRepository;
use Core\Users\Application\Services\UserService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind del Repository
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Bind del Service
        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
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