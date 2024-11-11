<?php

namespace Utopia\Telemetry\Adapter;

use Utopia\Telemetry\Adapter;
use Utopia\Telemetry\Counter;
use Utopia\Telemetry\Gauge;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\UpDownCounter;

class Noop implements Adapter
{
    public function createCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Counter
    {
        return new Counter();
    }

    public function createHistogram(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Histogram
    {
        return new Histogram();
    }

    public function createGauge(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Gauge
    {
        return new Gauge();
    }

    public function createUpDownCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): UpDownCounter
    {
        return new UpDownCounter();
    }

    public function collect(): bool
    {
        return true;
    }
}
