<?php

namespace App\Services\Template\Engines;

use App\Contracts\TemplateEngineInterface;
use Illuminate\Support\Facades\Blade;

class BladeTemplateEngine implements TemplateEngineInterface
{
    public function render(string $templateContent, array $variables): string
    {
        return Blade::render($templateContent, $variables);
    }

    public function supports(string $engineName): bool
    {
        return $engineName === 'blade';
    }
}
