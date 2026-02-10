<?php

namespace App\Enums;

enum SubscriberStatus: string
{
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';
    case Pending = 'pending';
}
