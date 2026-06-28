<?php

namespace App\Providers;

use App\Domains\Auth\Repositories\AuthRepositoryInterface;
use App\Infrastructure\Repositories\EloquentAuthRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, EloquentAuthRepository::class);
    }
}
