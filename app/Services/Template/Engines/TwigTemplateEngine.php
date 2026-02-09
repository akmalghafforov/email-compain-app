<?php

namespace App\Services\Template\Engines;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use App\Contracts\TemplateEngineInterface;

class TwigTemplateEngine implements TemplateEngineInterface
{
    public function render(string $templateContent, array $variables): string
    {
        $loader = new ArrayLoader(['template' => $templateContent]);
        $twig = new Environment($loader);

        return $twig->render('template', $variables);
    }

    public function supports(string $engineName): bool
    {
        return $engineName === 'twig';
    }
}
