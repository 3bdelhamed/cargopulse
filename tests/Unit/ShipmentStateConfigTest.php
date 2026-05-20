<?php

namespace Tests\Unit;

use App\Domains\Shipments\States\AssignedState;
use App\Domains\Shipments\States\ConfirmedState;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use App\Domains\Shipments\States\InTransitState;
use App\Domains\Shipments\States\PackedState;
use App\Domains\Shipments\States\PendingState;
use PHPUnit\Framework\TestCase;

class ShipmentStateConfigTest extends TestCase
{
    public function test_state_classes_expose_prd_status_names_and_colors(): void
    {
        $states = [
            PendingState::class => ['pending', 'gray'],
            ConfirmedState::class => ['confirmed', 'blue'],
            PackedState::class => ['packed', 'orange'],
            AssignedState::class => ['assigned', 'purple'],
            InTransitState::class => ['in_transit', 'yellow'],
            DeliveredState::class => ['delivered', 'green'],
            FailedState::class => ['failed', 'red'],
        ];

        foreach ($states as $class => [$name, $color]) {
            $this->assertSame($name, $class::$name);

            $state = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
            $this->assertSame($color, $state->color());
        }
    }
}
