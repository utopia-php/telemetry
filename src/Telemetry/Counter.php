<?php

namespace Utopia\Telemetry;

abstract class Counter
{
    abstract public function add(float|int $amount, iterable $attributes = []): void;
}
