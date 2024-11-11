<?php

namespace Utopia\Telemetry;

abstract class Histogram
{
    abstract public function record(float|int $amount, iterable $attributes = []): void;
}
