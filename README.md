# Utopia Telemetry

![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/telemetry.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://appwrite.io/discord)

Utopia Telemetry is a powerful Telemtry library. This library is aiming to be as simple and easy to learn and use. This library is maintained by the [Appwrite team](https://appwrite.io).

Although this library is part of the [Utopia System](https://github.com/utopia-php) project it is dependency free and can be used as standalone with any other PHP project or framework.

## Getting Started

Install using composer:

```bash
composer require utopia-php/telemetry
```

Init in your application:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

// Create a Telemetry instance using OpenTelemetry adapter.
use Utopia\Telemetry\Adapter\OpenTelemetry;

$telemetry = new OpenTelemetry('http://localhost:4138', 'namespace', 'app', 'unique-instance-name');
$counter = $telemetry->createUpDownCounter('http.server.active_requests', '{request}');

$counter->add(1);
$counter->add(2);

// Periodically collect metrics and send them to the configured OpenTelemetry endpoint.
$telemetry->collect();

// Example using Swoole
\Swoole\Timer::tick(60_000, fn () => $telemetry->collect());
```

## System Requirements

Utopia Framework requires PHP 8.0 or later. We recommend using the latest PHP version whenever possible.

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
