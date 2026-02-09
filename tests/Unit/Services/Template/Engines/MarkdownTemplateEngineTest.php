<?php

namespace Tests\Unit\Services\Template\Engines;

use Tests\TestCase;
use App\Services\Template\Engines\MarkdownTemplateEngine;

class MarkdownTemplateEngineTest extends TestCase
{
    /** @test */
    public function it_supports_markdown_engine(): void
    {
        $engine = new MarkdownTemplateEngine();
        $this->assertTrue($engine->supports('markdown'));
        $this->assertFalse($engine->supports('blade'));
    }

    /** @test */
    public function it_converts_markdown_to_html(): void
    {
        $engine = new MarkdownTemplateEngine();
        $markdown = '# Hello World';

        $result = $engine->render($markdown, []);

        $this->assertStringContainsString("<h1>Hello World</h1>", $result);
    }
}
