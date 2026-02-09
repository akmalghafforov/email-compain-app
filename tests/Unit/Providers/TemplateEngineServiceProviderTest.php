<?php

namespace Tests\Unit\Providers;

use App\Services\Template\Engines\BladeTemplateEngine;
use App\Services\Template\Engines\MarkdownTemplateEngine;
use App\Services\Template\Engines\TwigTemplateEngine;
use App\Services\Template\TemplateEngineRegistry;
use Tests\TestCase;

class TemplateEngineServiceProviderTest extends TestCase
{
    public function test_registry_is_bound_as_singleton()
    {
        $registry1 = $this->app->make(TemplateEngineRegistry::class);
        $registry2 = $this->app->make(TemplateEngineRegistry::class);

        $this->assertSame($registry1, $registry2);
    }

    public function test_registry_has_engines_registered()
    {
        $registry = $this->app->make(TemplateEngineRegistry::class);

        $this->assertInstanceOf(BladeTemplateEngine::class, $registry->resolve('blade'));
        $this->assertInstanceOf(TwigTemplateEngine::class, $registry->resolve('twig'));
        $this->assertInstanceOf(MarkdownTemplateEngine::class, $registry->resolve('markdown'));
    }
}
