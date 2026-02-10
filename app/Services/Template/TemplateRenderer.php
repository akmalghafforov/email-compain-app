<?php

namespace App\Services\Template;

use App\Models\Template;

class TemplateRenderer
{
    public function __construct(private TemplateEngineRegistry $registry) {}

    public function render(Template $template, array $variables): string
    {
        $engine = $this->registry->resolve($template->engine->value);

        return $engine->render($template->body_content, $variables);
    }
}
