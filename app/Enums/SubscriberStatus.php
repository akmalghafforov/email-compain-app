<?php

namespace App\Enums;

enum SubscriberStatus: string
{
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';
    case Pending = 'pending';
}
