<?php

namespace App\Domains\Fleet\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $driverId,
        public readonly float $lat,
        public readonly float $lng
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("tenant.{$this->tenantId}.tracking");
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }
}
