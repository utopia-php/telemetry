<?php

namespace Utopia\Telemetry;

abstract class UpDownCounter
{
    /**
     * @param iterable<non-empty-string, array<mixed>|bool|float|int|string|null> $attributes
     */
    abstract public function add(float|int $amount, iterable $attributes = []): void;
}
