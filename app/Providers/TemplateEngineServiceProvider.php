<?php

namespace App\Providers;

use App\Services\Template\Engines\BladeTemplateEngine;
use App\Services\Template\Engines\MarkdownTemplateEngine;
use App\Services\Template\Engines\TwigTemplateEngine;
use App\Services\Template\TemplateEngineRegistry;
use Illuminate\Support\ServiceProvider;

class TemplateEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TemplateEngineRegistry::class, function ($app) {
            $registry = new TemplateEngineRegistry();

            // Register available engines
            $registry->register($app->make(BladeTemplateEngine::class));
            $registry->register($app->make(TwigTemplateEngine::class));
            $registry->register($app->make(MarkdownTemplateEngine::class));

            return $registry;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
