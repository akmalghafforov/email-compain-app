<?php

namespace Tests\Unit\Services\Email;

use Mockery;
use Tests\TestCase;
use App\Services\EmailSenders\SmtpEmailSender;
use App\Contracts\EmailSenderInterface;

class SmtpEmailSenderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_implements_email_sender_interface(): void
    {
        // Arrange
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);
        $sender = new SmtpEmailSender($mailer);

        // Assert
        $this->assertInstanceOf(EmailSenderInterface::class, $sender);
    }

        /** @test */
    public function it_sends_email_via_mailer(): void
    {
        $mailer = Mockery::mock(\Illuminate\Mail\Mailer::class);
        $subscriber = Mockery::mock(\App\Contracts\Subscriber\Sendable::class);

        $subscriber->shouldReceive('getEmail')
            ->once()
            ->andReturn('test@example.com');

        $subscriber->shouldReceive('getName')
            ->once()
            ->andReturn('John Doe');

        $mailer->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type('array'),  // view data
                Mockery::type('array'),  // variables
                Mockery::on(function ($callback) use ($subscriber) {
                    $message = Mockery::mock(\Illuminate\Mail\Message::class);
                    $message->shouldReceive('to')
                        ->with('test@example.com', 'John Doe')
                        ->once()
                        ->andReturnSelf();
                    $message->shouldReceive('subject')
                        ->with('Test Subject')
                        ->once()
                        ->andReturnSelf();

                    $callback($message);
                    return true;
                })
            );

        $sender = new SmtpEmailSender($mailer);

        $result = $sender->send($subscriber, 'Test Subject', '<p>Test Body</p>');

        $this->assertInstanceOf(\App\DTOs\SendResult::class, $result);
    }
}
