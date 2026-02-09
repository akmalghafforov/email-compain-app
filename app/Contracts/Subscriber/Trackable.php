<?php

declare(strict_types=1);

namespace App\Contracts\Subscriber;

interface Trackable
{
    public function getTrackingId(): string;

    /**
     * @param array<string, mixed> $payload
     */
    public function recordEvent(string $event, array $payload = []): void;
}
