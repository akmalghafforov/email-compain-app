<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\TestCase;
use App\Contracts\EmailSenderInterface;
use App\Services\EmailSenders\SmtpEmailSender;
use App\Services\EmailSenders\SendGridEmailSender;
use App\Services\EmailSenders\MailgunEmailSender;

class EmailServiceProviderTest extends TestCase
{
    public function test_default_sender_is_smtp(): void
    {
        config(['campaigns.default_sender' => 'smtp']);

        $sender = $this->app->make(EmailSenderInterface::class);

        $this->assertInstanceOf(SmtpEmailSender::class, $sender);
    }

    public function test_sendgrid_sender_is_resolved_when_configured(): void
    {
        config([
            'campaigns.default_sender' => 'sendgrid',
            'campaigns.sendgrid.from_email' => 'test@example.com',
            'campaigns.sendgrid.from_name' => 'Test Sender',
        ]);

        $sender = $this->app->make(EmailSenderInterface::class);

        $this->assertInstanceOf(SendGridEmailSender::class, $sender);
    }

    public function test_mailgun_sender_is_resolved_when_configured(): void
    {
        config([
            'campaigns.default_sender' => 'mailgun',
            'campaigns.mailgun.domain' => 'mg.example.com',
            'campaigns.mailgun.from_email' => 'test@example.com',
            'campaigns.mailgun.from_name' => 'Test Sender',
        ]);

        $sender = $this->app->make(EmailSenderInterface::class);

        $this->assertInstanceOf(MailgunEmailSender::class, $sender);
    }

    public function test_smtp_is_fallback_for_unknown_drivers(): void
    {
        config(['campaigns.default_sender' => 'unknown-driver']);

        $sender = $this->app->make(EmailSenderInterface::class);

        $this->assertInstanceOf(SmtpEmailSender::class, $sender);
    }

    public function test_sender_is_singleton(): void
    {
        config(['campaigns.default_sender' => 'smtp']);

        $sender1 = $this->app->make(SmtpEmailSender::class);
        $sender2 = $this->app->make(SmtpEmailSender::class);

        $this->assertSame($sender1, $sender2);
    }

    public function test_each_concrete_sender_can_be_resolved_directly(): void
    {
        config([
            'campaigns.sendgrid.from_email' => 'test@example.com',
            'campaigns.sendgrid.from_name' => 'Test',
            'campaigns.mailgun.domain' => 'mg.example.com',
            'campaigns.mailgun.from_email' => 'test@example.com',
            'campaigns.mailgun.from_name' => 'Test',
        ]);

        $this->assertInstanceOf(SmtpEmailSender::class, $this->app->make(SmtpEmailSender::class));
        $this->assertInstanceOf(SendGridEmailSender::class, $this->app->make(SendGridEmailSender::class));
        $this->assertInstanceOf(MailgunEmailSender::class, $this->app->make(MailgunEmailSender::class));
    }

    public function test_all_senders_implement_email_sender_interface(): void
    {
        config([
            'campaigns.sendgrid.from_email' => 'test@example.com',
            'campaigns.sendgrid.from_name' => 'Test',
            'campaigns.mailgun.domain' => 'mg.example.com',
            'campaigns.mailgun.from_email' => 'test@example.com',
            'campaigns.mailgun.from_name' => 'Test',
        ]);

        $smtp = $this->app->make(SmtpEmailSender::class);
        $sendgrid = $this->app->make(SendGridEmailSender::class);
        $mailgun = $this->app->make(MailgunEmailSender::class);

        $this->assertInstanceOf(EmailSenderInterface::class, $smtp);
        $this->assertInstanceOf(EmailSenderInterface::class, $sendgrid);
        $this->assertInstanceOf(EmailSenderInterface::class, $mailgun);
    }

    public function test_switching_drivers_at_runtime(): void
    {
        config(['campaigns.default_sender' => 'smtp']);
        $sender1 = $this->app->make(EmailSenderInterface::class);
        $this->assertInstanceOf(SmtpEmailSender::class, $sender1);

        $this->app->forgetInstance(EmailSenderInterface::class);
        config([
            'campaigns.default_sender' => 'sendgrid',
            'campaigns.sendgrid.from_email' => 'test@example.com',
            'campaigns.sendgrid.from_name' => 'Test',
        ]);

        $sender2 = $this->app->make(EmailSenderInterface::class);
        $this->assertInstanceOf(SendGridEmailSender::class, $sender2);
    }
}
