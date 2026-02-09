<?php

declare(strict_types=1);

namespace App\Contracts;

interface TemplateEngineInterface
{
    /**
     * Render a template string with the given variables.
     *
     * @param array<string, mixed> $variables
     *
     * @throws \Throwable If rendering fails
     */
    public function render(string $templateContent, array $variables): string;

    /**
     * Determine if this engine supports the given engine name.
     */
    public function supports(string $engineName): bool;
}
