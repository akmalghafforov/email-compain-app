<?php

namespace Tests\Unit\Services\Template\Engines;

use Tests\TestCase;
use App\Services\Template\TemplateRenderer;
use \App\Services\Template\TemplateEngineRegistry;
use App\Services\Template\Engines\TwigTemplateEngine;

class TwigTemplateEngineTest extends TestCase
{
    /** @test */
    public function it_supports_twig_engine(): void
    {
        $engine = new TwigTemplateEngine();
        $this->assertTrue($engine->supports('twig'));
        $this->assertFalse($engine->supports('markdown'));
    }

    /** @test */
    public function it_renders_twig_template(): void
    {
        $engine = new TwigTemplateEngine();
        $template = '<h1>Hello World</h1>';

        $result = $engine->render($template, []);

        $this->assertStringContainsString('<h1>Hello World</h1>', $result);
    }

    /** @test */
    public function it_renders_with_variable_substitution(): void
    {
        $engine = new TwigTemplateEngine();
        $template = '<h1>Hello {{ name }}!</h1>';
        $variables = ['name' => 'Akmal'];

        $result = $engine->render($template, $variables);

        $this->assertStringContainsString('<h1>Hello Akmal!</h1>', $result);
    }

    /** @test */
    public function it_renders_twig_control_structures(): void
    {
        $engine = new TwigTemplateEngine();
        $template = '{% if active %}<p>Active</p>{% else %}<p>Inactive</p>{% endif %}';
        $variables = ['active' => true];

        $result = $engine->render($template, $variables);

        $this->assertStringContainsString('<p>Active</p>', $result);
        $this->assertStringNotContainsString('<p>Inactive</p>', $result);
    }

    /** @test */
    public function it_works_with_template_renderer(): void
    {
        $registry = new TemplateEngineRegistry();
        $registry->register(new TwigTemplateEngine());

        $renderer = new TemplateRenderer($registry);

        $template = new \App\Models\Template([
            'engine' => 'twig',
            'body_content' => '<h1>Welcome {{ name }}!</h1><p>You have <strong>{{ count }}</strong> new messages.</p>',
        ]);

        $variables = ['name' => 'Bob', 'count' => 5];
        $result = $renderer->render($template, $variables);

        $this->assertStringContainsString('<h1>Welcome Bob!</h1>', $result);
        $this->assertStringContainsString('<strong>5</strong>', $result);
    }
}
