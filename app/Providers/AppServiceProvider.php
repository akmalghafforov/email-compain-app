<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Delivery\DeliveryTracker;
use App\Contracts\DeliveryTrackerInterface;
use App\Repositories\EloquentCampaignRepository;
use App\Repositories\EloquentTemplateRepository;
use App\Repositories\EloquentSubscriberRepository;
use App\Contracts\Repositories\CampaignRepositoryInterface;
use App\Contracts\Repositories\TemplateRepositoryInterface;
use App\Contracts\Repositories\SubscriberRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CampaignRepositoryInterface::class,
            EloquentCampaignRepository::class
        );

        $this->app->bind(
            SubscriberRepositoryInterface::class,
            EloquentSubscriberRepository::class
        );

        $this->app->bind(
            TemplateRepositoryInterface::class,
            EloquentTemplateRepository::class
        );

        $this->app->bind(
            DeliveryTrackerInterface::class,
            DeliveryTracker::class
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
