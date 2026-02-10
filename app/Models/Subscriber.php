<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Contracts\Subscriber\Sendable;

class Subscriber extends Model implements Sendable
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'status',
        'metadata',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)
            ->using(CampaignSubscriber::class)
            ->withPivot('status', 'sent_at', 'opened_at', 'clicked_at', 'failed_reason')
            ->withTimestamps(createdAt: 'created_at', updatedAt: false);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(DeliveryLog::class);
    }
}
