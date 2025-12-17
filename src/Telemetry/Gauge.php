<?php

namespace Utopia\Telemetry;

abstract class Gauge
{
    /**
     * @param iterable<non-empty-string, array<mixed>|bool|float|int|string|null> $attributes
     */
    abstract public function record(float|int $amount, iterable $attributes = []): void;
}
