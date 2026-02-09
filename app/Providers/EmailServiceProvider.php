<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Mail\Mailer;
use Psr\Log\LoggerInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

use App\Contracts\EmailSenderInterface;
use App\Services\EmailSenders\SmtpEmailSender;
use App\Services\EmailSenders\MailgunEmailSender;
use App\Services\EmailSenders\SendGridEmailSender;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register email sender services.
     */
    public function register(): void
    {
        // Register each concrete email sender implementation
        $this->registerSmtpSender();
        $this->registerSendGridSender();
        $this->registerMailgunSender();

        // Bind the EmailSenderInterface to the configured driver
        $this->app->bind(EmailSenderInterface::class, function (Application $app) {
            $driver = config('campaigns.default_sender', 'smtp');

            return match ($driver) {
                'sendgrid' => $app->make(SendGridEmailSender::class),
                'mailgun' => $app->make(MailgunEmailSender::class),
                default => $app->make(SmtpEmailSender::class),
            };
        });
    }

    /**
     * Bootstrap email sender services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register the SMTP email sender.
     */
    private function registerSmtpSender(): void
    {
        $this->app->singleton(SmtpEmailSender::class, function (Application $app) {
            return new SmtpEmailSender(
                $app->make(Mailer::class)
            );
        });
    }

    /**
     * Register the SendGrid email sender.
     */
    private function registerSendGridSender(): void
    {
        $this->app->singleton(SendGridEmailSender::class, function (Application $app) {
            return new SendGridEmailSender(
                $app->make(LoggerInterface::class),
                config('campaigns.sendgrid.from_email'),
                config('campaigns.sendgrid.from_name')
            );
        });
    }

    /**
     * Register the Mailgun email sender.
     */
    private function registerMailgunSender(): void
    {
        $this->app->singleton(MailgunEmailSender::class, function (Application $app) {
            return new MailgunEmailSender(
                $app->make(LoggerInterface::class),
                config('campaigns.mailgun.domain'),
                config('campaigns.mailgun.from_email'),
                config('campaigns.mailgun.from_name')
            );
        });
    }
}
