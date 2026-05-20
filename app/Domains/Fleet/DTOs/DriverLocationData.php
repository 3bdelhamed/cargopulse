<?php

namespace App\Domains\Fleet\DTOs;

use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Data;

class DriverLocationData extends Data
{
    public function __construct(
        #[Numeric]
        public readonly float $lat,
        
        #[Numeric]
        public readonly float $lng,
        
        #[Numeric]
        public readonly int $timestamp,
    ) {}
}
