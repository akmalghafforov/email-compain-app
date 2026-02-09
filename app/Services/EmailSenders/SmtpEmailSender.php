<?php

namespace App\Services\EmailSenders;

use Illuminate\Support\Str;
use Illuminate\Mail\Mailer;

use App\DTOs\SendResult;
use App\DTOs\BatchResult;
use App\Contracts\Subscriber\Sendable;
use App\Contracts\EmailSenderInterface;

class SmtpEmailSender implements EmailSenderInterface
{
    public function __construct(
        private Mailer $mailer
    ) {}

    public function send(Sendable $subscriber, string $subject, string $htmlBody): SendResult
    {
        $messageId = $this->generateMessageId();

        $this->mailer->send(
            ['html' => 'emails.raw'],
            ['content' => $htmlBody],
            function ($message) use ($subscriber, $subject) {
                $message->to($subscriber->getEmail(), $subscriber->getName())
                    ->subject($subject);
            }
        );

        return new SendResult($messageId, 'sent');
    }

    public function sendBatch(array $recipients, string $subject, string $body): BatchResult
    {
        throw new \Exception('Not implemented');
    }

    private function generateMessageId(): string
    {
        return Str::uuid()->toString();
    }
}
