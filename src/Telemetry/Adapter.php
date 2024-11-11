<?php

namespace Utopia\Telemetry;

interface Adapter
{
    public function createCounter(
        string $name,
        ?string $unit = null,
        ?string $description = null,
        array $advisory = [],
    ): Counter;

    public function createHistogram(
        string $name,
        ?string $unit = null,
        ?string $description = null,
        array $advisory = [],
    ): Histogram;

    public function createGauge(
        string $name,
        ?string $unit = null,
        ?string $description = null,
        array $advisory = [],
    ): Gauge;

    public function createUpDownCounter(
        string $name,
        ?string $unit = null,
        ?string $description = null,
        array $advisory = [],
    ): UpDownCounter;

    public function collect(): bool;
}
