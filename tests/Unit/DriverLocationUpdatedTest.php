<?php

namespace Tests\Unit;

use App\Domains\Fleet\Events\DriverLocationUpdated;
use PHPUnit\Framework\TestCase;

class DriverLocationUpdatedTest extends TestCase
{
    public function test_driver_location_updated_formats_data_correctly_for_reverb(): void
    {
        $event = new DriverLocationUpdated(1, 5, 30.0444, 31.2357);

        $channel = $event->broadcastOn();
        $this->assertSame("tenant.1.tracking", $channel->name);
        
        $this->assertSame('location.updated', $event->broadcastAs());
    }
}
