<?php

namespace Tests\Unit\Services\Template\Engines;

use Tests\TestCase;
use App\Services\Template\Engines\MarkdownTemplateEngine;

class MarkdownTemplateEngineTest extends TestCase
{
    public function test_it_supports_markdown_engine(): void
    {
        $engine = new MarkdownTemplateEngine();
        $this->assertTrue($engine->supports('markdown'));
        $this->assertFalse($engine->supports('blade'));
    }

    public function test_it_converts_markdown_to_html(): void
    {
        $engine = new MarkdownTemplateEngine();
        $markdown = '# Hello World';

        $result = $engine->render($markdown, []);

        $this->assertStringContainsString("<h1>Hello World</h1>", $result);
    }

    public function test_it_renders_with_variables_substitution(): void
    {
        $engine = new MarkdownTemplateEngine();
        $markdown = '# Hello {{name}}!';
        $variables = [
            'name' => 'Akmal',
        ];

        $result = $engine->render($markdown, $variables);

        $this->assertStringContainsString('<h1>Hello Akmal!</h1>', $result);
    }

    public function test_it_works_with_template_rendere()
    {
        $registry = new \App\Services\Template\TemplateEngineRegistry();
        $registry->register(new MarkdownTemplateEngine());

        $renderer = new \App\Services\Template\TemplateRenderer($registry);

        $template = new \App\Models\Template([
            'engine' => 'markdown',
            'body_content' => "# Welcome {{name}}!\n\nYou have **{{count}}** new messages."
        ]);

        $variables = ['name' => 'Bob', 'count' => 5];
        $result = $renderer->render($template, $variables);

        $this->assertStringContainsString('<h1>Welcome Bob!</h1>', $result);
        $this->assertStringContainsString('<strong>5</strong>', $result);
    }
}
