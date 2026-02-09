<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class BatchResult
{
    /**
     * @param list<SendResult> $results
     */
    public function __construct(
        public int $totalSent,
        public int $totalFailed,
        public array $results,
    ) {}

    public function hasFailures(): bool
    {
        return $this->totalFailed > 0;
    }

    /**
     * @return list<SendResult>
     */
    public function getSuccessful(): array
    {
        return array_values(
            array_filter($this->results, fn (SendResult $r): bool => $r->isSuccessful())
        );
    }

    /**
     * @return list<SendResult>
     */
    public function getFailed(): array
    {
        return array_values(
            array_filter($this->results, fn (SendResult $r): bool => !$r->isSuccessful())
        );
    }
}
