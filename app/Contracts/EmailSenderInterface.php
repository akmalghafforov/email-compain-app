<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Contracts\Subscriber\Sendable;
use App\DTOs\BatchResult;
use App\DTOs\SendResult;

interface EmailSenderInterface
{
    /**
     * Send an email to a single recipient.
     *
     * @throws \Throwable If the send operation fails
     */
    public function send(Sendable $recipient, string $subject, string $body): SendResult;

    /**
     * Send an email to multiple recipients in a batch.
     *
     * @param list<Sendable> $recipients
     *
     * @throws \Throwable If the batch send operation fails
     */
    public function sendBatch(array $recipients, string $subject, string $body): BatchResult;
}
