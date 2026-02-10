<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Delivery\DeliveryTracker;
use App\Contracts\DeliveryTrackerInterface;
use App\Contracts\CampaignRepositoryInterface;
use App\Contracts\SubscriberRepositoryInterface;
use App\Services\Campaign\EloquentCampaignRepository;
use App\Services\Subscriber\EloquentSubscriberRepository;

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
