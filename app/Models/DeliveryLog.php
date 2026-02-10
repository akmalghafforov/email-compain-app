<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Enums\DeliveryLogEvent;

class DeliveryLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'channel',
        'event',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => DeliveryLogEvent::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
