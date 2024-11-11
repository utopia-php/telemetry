<?php

namespace Utopia\Telemetry;

class Gauge
{
    public function record(float|int $amount, iterable $attributes = []): void
    {
    }
}
