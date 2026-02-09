<?php

namespace App\Services\EmailSenders;

use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

use App\DTOs\SendResult;
use App\DTOs\BatchResult;
use App\Contracts\Subscriber\Sendable;
use App\Contracts\EmailSenderInterface;
use App\Exceptions\SendFailedException;

class SendGridEmailSender implements EmailSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private string $fromEmail,
        private string $fromName,
    ) {}

    public function send(Sendable $recipient, string $subject, string $body): SendResult
    {
        $messageId = $this->generateMessageId();

        try {
            $this->logger->info('SendGrid: Sending email', [
                'to' => $recipient->getEmail(),
                'name' => $recipient->getName(),
                'from' => "{$this->fromName} <{$this->fromEmail}>",
                'subject' => $subject,
            ]);
        } catch (\Throwable $e) {
            throw new SendFailedException('Failed to send email via SendGrid', 0, $e);
        }

        return new SendResult($messageId, 'sent');
    }

    public function sendBatch(array $recipients, string $subject, string $body): BatchResult
    {
        $results = array_map(fn(Sendable $recipient) => $this->attemptSend($recipient, $subject, $body), $recipients);

        $totalSent = count(array_filter($results, fn(SendResult $r) => $r->isSuccessful()));

        return new BatchResult($totalSent, count($results) - $totalSent, $results);
    }

    private function attemptSend(Sendable $recipient, string $subject, string $body): SendResult
    {
        try {
            return $this->send($recipient, $subject, $body);
        } catch (\Throwable) {
            return new SendResult($this->generateMessageId(), 'failed');
        }
    }

    private function generateMessageId(): string
    {
        return Str::uuid()->toString();
    }
}
