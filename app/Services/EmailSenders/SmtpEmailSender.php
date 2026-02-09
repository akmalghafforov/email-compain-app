<?php

namespace App\Services\EmailSenders;

use App\DTOs\SendResult;
use App\DTOs\BatchResult;
use App\Contracts\EmailSenderInterface;
use App\Contracts\Subscriber\Sendable;

class SmtpEmailSender implements EmailSenderInterface
{
    public function send(Sendable $subscriber, string $subject, string $htmlBody): SendResult
    {
        return new SendResult('temp-id', 'sent');
    }

    public function sendBatch(array $recipients, string $subject, string $body): BatchResult
    {
        throw new \Exception('Not implemented');
    }
}
