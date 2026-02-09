<?php

namespace App\Services\Template\Engines;

use App\Contracts\TemplateEngineInterface;

class MarkdownTemplateEngine implements TemplateEngineInterface
{
    public function render(string $templateContent, array $variables): string
    {
        return ''; // Not implemented yet
    }

    public function supports(string $engineName): bool
    {
        return $engineName === 'markdown';
    }
}
