<?php

declare(strict_types=1);

namespace App\Contracts\Subscriber;

interface Unsubscribable
{
    public function unsubscribe(string $reason = ''): void;

    public function isSubscribed(): bool;
}
