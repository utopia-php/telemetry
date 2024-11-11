<?php

namespace Utopia\Telemetry;

abstract class Gauge
{
    abstract public function record(float|int $amount, iterable $attributes = []): void;
}
