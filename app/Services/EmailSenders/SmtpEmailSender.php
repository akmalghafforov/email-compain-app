<?php

namespace App\Services\EmailSenders;

use Illuminate\Support\Str;
use Illuminate\Mail\Mailer;

use App\DTOs\SendResult;
use App\DTOs\BatchResult;
use App\Contracts\Subscriber\Sendable;
use App\Contracts\EmailSenderInterface;
use App\Exceptions\SendFailedException;

class SmtpEmailSender implements EmailSenderInterface
{
    public function __construct(
        private Mailer $mailer
    ) {}

    public function send(Sendable $subscriber, string $subject, string $htmlBody): SendResult
    {
        $messageId = $this->generateMessageId();

        try {
            $this->mailer->send(
                ['html' => 'emails.raw'],
                ['content' => $htmlBody],
                function ($message) use ($subscriber, $subject) {
                    $message->to($subscriber->getEmail(), $subscriber->getName())
                        ->subject($subject);
                }
            );
        } catch (\Throwable $e) {
            throw new SendFailedException('Failed to send email', 0, $e);
        }

        return new SendResult($messageId, 'sent');
    }

    public function sendBatch(array $recipients, string $subject, string $body): BatchResult
    {
        $results = [];
        $totalSent = 0;
        $totalFailed = 0;

        foreach ($recipients as $recipient) {
            try {
                $result = $this->send($recipient, $subject, $body);
                $results[] = $result;
                $totalSent++;
            } catch (\Throwable $e) {
                $results[] = new SendResult($this->generateMessageId(), 'failed');
                $totalFailed++;
            }
        }

        return new BatchResult($totalSent, $totalFailed, $results);
    }

    private function generateMessageId(): string
    {
        return Str::uuid()->toString();
    }
}
