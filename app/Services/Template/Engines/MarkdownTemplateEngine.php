<?php

namespace App\Services\Template\Engines;

use App\Contracts\TemplateEngineInterface;
use League\CommonMark\CommonMarkConverter;

class MarkdownTemplateEngine implements TemplateEngineInterface
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter(); // todo wrap with adapter
    }

    public function render(string $templateContent, array $variables): string
    {
        return $this->converter->convert($templateContent)->getContent();
    }

    public function supports(string $engineName): bool
    {
        return $engineName === 'markdown';
    }
}
