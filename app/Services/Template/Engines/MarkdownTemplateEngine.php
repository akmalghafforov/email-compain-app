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
        $content = $this->replaceVariables($templateContent, $variables);

        return $this->converter->convert($content)->getContent();
    }

    public function supports(string $engineName): bool
    {
        return $engineName === 'markdown';
    }

    private function replaceVariables(string $content, array $variables): string
    {
        $search = array_map(fn($key) => "{{" . $key . "}}", array_keys($variables));
        $replace = array_values($variables);

        return str_replace($search, $replace, $content);
    }
}
