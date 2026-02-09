<?php

declare(strict_types=1);

namespace App\Contracts\Subscriber;

interface Sendable
{
    public function getEmail(): string;

    public function getName(): ?string;
}
