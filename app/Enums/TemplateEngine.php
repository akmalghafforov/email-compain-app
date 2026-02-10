<?php

namespace App\Enums;

enum TemplateEngine: string
{
    case Blade = 'blade';
    case Twig = 'twig';
    case Markdown = 'markdown';
    case Mjml = 'mjml';
}
