<?php

namespace Utopia\Telemetry\Adapter;

use Utopia\Telemetry\Adapter;
use Utopia\Telemetry\Counter;
use Utopia\Telemetry\Gauge;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\UpDownCounter;

class None implements Adapter
{
    public function createCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Counter
    {
        return new class () extends Counter {
            public function add(float|int $amount, iterable $attributes = []): void
            {
            }
        };
    }

    public function createHistogram(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Histogram
    {
        return new class () extends Histogram {
            public function record(float|int $amount, iterable $attributes = []): void
            {
            }
        };
    }

    public function createGauge(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Gauge
    {
        return new class () extends Gauge {
            public function record(float|int $amount, iterable $attributes = []): void
            {
            }
        };
    }

    public function createUpDownCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): UpDownCounter
    {
        return new class () extends UpDownCounter {
            public function add(float|int $amount, iterable $attributes = []): void
            {
            }
        };
    }

    public function collect(): bool
    {
        return true;
    }
}
