<?php

namespace Utopia\Telemetry;

abstract class UpDownCounter
{
    abstract public function add(float|int $amount, iterable $attributes = []): void;
}
