<?php

namespace App\Enums;

enum DeliveryLogEvent: string
{
    case Sent = 'sent';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Bounced = 'bounced';
    case Failed = 'failed';
}
