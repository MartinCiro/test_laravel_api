<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Users\Ports\UserRepositoryInterface;
use Infrastructure\Persistence\Eloquent\Repositories\UserRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind de interfaces con sus implementaciones
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Futuros repositorios se agregarán aquí
        // $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
    }

    public function boot(): void
    {
        //
    }
}