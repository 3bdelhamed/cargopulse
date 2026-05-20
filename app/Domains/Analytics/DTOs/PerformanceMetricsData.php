<?php

namespace App\Domains\Analytics\DTOs;

use Spatie\LaravelData\Data;

class PerformanceMetricsData extends Data
{
    public function __construct(
        public readonly float $delivery_success_rate,
        public readonly float $average_delivery_duration_hours,
        public readonly float $failed_to_returned_ratio,
        public readonly int $active_drivers_count,
    ) {}
}
