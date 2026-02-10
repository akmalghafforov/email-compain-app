<?php

namespace App\Enums;

enum CampaignSubscriberStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
