<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class SendResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $messageId,
        public string $status,
        public array $metadata = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === 'sent';
    }
}
