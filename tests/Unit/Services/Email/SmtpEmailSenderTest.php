<?php

namespace Tests\Unit\Services\Email;

use Tests\TestCase;
use App\Services\Email\SmtpEmailSender;
use App\Contracts\EmailSenderInterface;
use Mockery;

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
}
