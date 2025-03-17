<?php

namespace Utopia\Telemetry\Adapter;

use Utopia\Telemetry\Adapter;
use Utopia\Telemetry\Counter;
use Utopia\Telemetry\Gauge;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\UpDownCounter;

/**
 * Test adapter allows access to the underlying telemetry resources. Can be used in tests to verify metrics.
 */
class Test implements Adapter
{
    public array $counters = [];
    public array $histograms = [];
    public array $gauges = [];
    public array $upDownCounters = [];

    public function createCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Counter
    {
        $counter = new class () extends Counter {
            public array $values = [];
            public function add(float|int $amount, iterable $attributes = []): void
            {
                $this->values[] = $amount;
            }
        };
        $this->counters[$name] = $counter;
        return $counter;
    }

    public function createHistogram(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Histogram
    {
        $histogram = new class () extends Histogram {
            public array $values = [];
            public function record(float|int $amount, iterable $attributes = []): void
            {
                $this->values[] = $amount;
            }
        };
        $this->histograms[$name] = $histogram;
        return $histogram;
    }

    public function createGauge(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Gauge
    {
        $gauge = new class () extends Gauge {
            public array $values = [];
            public function record(float|int $amount, iterable $attributes = []): void
            {
                $this->values[] = $amount;
            }
        };
        $this->gauges[$name] = $gauge;
        return $gauge;
    }

    public function createUpDownCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): UpDownCounter
    {
        $upDownCounter = new class () extends UpDownCounter {
            public array $values = [];
            public function add(float|int $amount, iterable $attributes = []): void
            {
                $this->values[] = $amount;
            }
        };
        $this->upDownCounters[$name] = $upDownCounter;
        return $upDownCounter;
    }

    public function collect(): bool
    {
        return true;
    }
}
