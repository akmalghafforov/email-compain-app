<?php

namespace Tests\Unit\Services\Template\Engines;

use Tests\TestCase;
use App\Services\Template\Engines\BladeTemplateEngine;

class BladeTemplateEngineTest extends TestCase
{
    public function test_it_supports_blade_engine(): void
    {
        $engine = new BladeTemplateEngine();
        $this->assertTrue($engine->supports('blade'));
        $this->assertFalse($engine->supports('markdown'));
    }

    public function test_it_renders_blade_template(): void
    {
        $engine = new BladeTemplateEngine();
        $template = '<h1>Hello World</h1>';

        $result = $engine->render($template, []);

        $this->assertStringContainsString('<h1>Hello World</h1>', $result);
    }

    public function test_it_renders_with_variable_substitution(): void
    {
        $engine = new BladeTemplateEngine();
        $template = '<h1>Hello {{ $name }}!</h1>';
        $variables = ['name' => 'Akmal'];

        $result = $engine->render($template, $variables);

        $this->assertStringContainsString('<h1>Hello Akmal!</h1>', $result);
    }

    public function test_it_renders_blade_directives(): void
    {
        $engine = new BladeTemplateEngine();
        $template = '@if($active)<p>Active</p>@else<p>Inactive</p>@endif';
        $variables = ['active' => true];

        $result = $engine->render($template, $variables);

        $this->assertStringContainsString('<p>Active</p>', $result);
        $this->assertStringNotContainsString('<p>Inactive</p>', $result);
    }

    public function test_it_works_with_template_renderer(): void
    {
        $registry = new \App\Services\Template\TemplateEngineRegistry();
        $registry->register(new BladeTemplateEngine());

        $renderer = new \App\Services\Template\TemplateRenderer($registry);

        $template = new \App\Models\Template([
            'engine' => 'blade',
            'body_content' => '<h1>Welcome {{ $name }}!</h1><p>You have <strong>{{ $count }}</strong> new messages.</p>',
        ]);

        $variables = ['name' => 'Bob', 'count' => 5];
        $result = $renderer->render($template, $variables);

        $this->assertStringContainsString('<h1>Welcome Bob!</h1>', $result);
        $this->assertStringContainsString('<strong>5</strong>', $result);
    }
}
