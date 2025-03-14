<?php

namespace Utopia\Telemetry;

abstract class Span
{
    abstract public function end(): void;
}
