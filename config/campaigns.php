<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Email Sender
    |--------------------------------------------------------------------------
    |
    | This option controls the default email sender driver used to send
    | campaign emails. Supported: "smtp", "sendgrid", "mailgun"
    |
    */
    'default_sender' => env('CAMPAIGN_SENDER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Default Template Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default template engine used to render
    | email templates. Supported: "blade", "twig", "markdown", "mjml"
    |
    */
    'default_engine' => env('CAMPAIGN_ENGINE', 'blade'),

    /*
    |--------------------------------------------------------------------------
    | SendGrid Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your SendGrid API settings for sending
    | campaign emails via the SendGrid service.
    |
    */
    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'from_email' => env('SENDGRID_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
        'from_name' => env('SENDGRID_FROM_NAME', env('MAIL_FROM_NAME', 'Campaign Manager')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailgun Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Mailgun API settings for sending
    | campaign emails via the Mailgun service.
    |
    */
    'mailgun' => [
        'api_key' => env('MAILGUN_API_KEY'),
        'domain' => env('MAILGUN_DOMAIN'),
        'from_email' => env('MAILGUN_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
        'from_name' => env('MAILGUN_FROM_NAME', env('MAIL_FROM_NAME', 'Campaign Manager')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | These options control the tracking features for campaign emails,
    | including open tracking, click tracking, and webhook security.
    |
    */
    'tracking' => [
        'open_tracking' => env('CAMPAIGN_TRACK_OPENS', true),
        'click_tracking' => env('CAMPAIGN_TRACK_CLICKS', true),
        'webhook_secret' => env('CAMPAIGN_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | These options control the queue settings for campaign email sending,
    | including the queue connection and batch size.
    |
    */
    'queue' => [
        'connection' => env('CAMPAIGN_QUEUE', 'redis'),
        'batch_size' => env('CAMPAIGN_BATCH_SIZE', 100),
    ],
];
