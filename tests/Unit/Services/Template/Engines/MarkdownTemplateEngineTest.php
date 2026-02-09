<?php

namespace Tests\Unit\Services\Template\Engines;

use Tests\TestCase;
use App\Services\Template\Engines\MarkdownTemplateEngine;

class MarkdownTemplateEngineTest extends TestCase
{
    /** @test */
    public function it_supports_markdown_engine(): void
    {
        // Arrange
        $engine = new MarkdownTemplateEngine();
        // Act & Assert
        $this->assertTrue($engine->supports('markdown'));
        $this->assertFalse($engine->supports('blade'));
    }
}
