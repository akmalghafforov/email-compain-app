<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\SubscriberRepositoryInterface;
use App\Services\Subscriber\EloquentSubscriberRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            SubscriberRepositoryInterface::class,
            EloquentSubscriberRepository::class
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
