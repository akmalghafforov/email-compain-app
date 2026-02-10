<?php

namespace App\Enums;

enum DeliveryLogEvent: string
{
    case Sent = 'sent';
    case Opened = 'opened';
    case Clicked = 'clicked';

    case Failed = 'failed';
}
