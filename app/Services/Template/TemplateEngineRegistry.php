<?php

namespace App\Services\Template;

use App\Contracts\TemplateEngineInterface;
use App\Exceptions\UnsupportedEngineException;

class TemplateEngineRegistry
{
    /** @var TemplateEngineInterface[] */
    private array $engines = [];

    public function register(TemplateEngineInterface $engine): void
    {
        $this->engines[] = $engine;
    }

    public function resolve(string $engineName): TemplateEngineInterface
    {
        foreach ($this->engines as $engine) {
            if ($engine->supports($engineName)) return $engine;
        }

        throw new UnsupportedEngineException($engineName);
    }
}
