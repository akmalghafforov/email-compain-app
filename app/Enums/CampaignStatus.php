<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Started = 'started';
    case CollectingSubscribers = 'collecting_subscribers';
    case SubscribersCollected = 'subscribers_collected';
    case Sending = 'sending';
    case Sent = 'sent';
    case PartiallySent = 'partially_sent';
    case Failed = 'failed';
}
