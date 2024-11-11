<?php

namespace Utopia\Telemetry\Adapter;

use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\UpDownCounterInterface;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricExporterInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SemConv\ResourceAttributes;
use Utopia\Telemetry\Adapter;
use Utopia\Telemetry\Counter;
use Utopia\Telemetry\Gauge;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\UpDownCounter;

class OpenTelemetry implements Adapter
{
    private MetricReaderInterface $reader;
    private MeterInterface $meter;

    public function __construct(string $endpoint, string $serviceNamespace, string $serviceName, string $serviceInstanceId)
    {
        $exporter = $this->createExporter($endpoint);
        $attributes = Attributes::create([
            'service.namespace' => $serviceNamespace,
            'service.name' => $serviceName,
            'service.instance.id' => $serviceInstanceId,
        ]);
        $this->meter = $this->initMeter($exporter, $attributes);
    }

    protected function initMeter(MetricExporterInterface $exporter, Attributes $attributes): MeterInterface
    {
        $this->reader = new ExportingReader($exporter);
        $meterProvider = MeterProvider::builder()
            ->setResource(ResourceInfo::create($attributes, ResourceAttributes::SCHEMA_URL))
            ->addReader($this->reader)
            ->build();

        Sdk::builder()->setMeterProvider($meterProvider)->buildAndRegisterGlobal();

        return $meterProvider->getMeter('cloud');
    }

    protected function createExporter(string $endpoint): MetricExporterInterface
    {
        $transport = (new OtlpHttpTransportFactory())->create($endpoint, ContentTypes::PROTOBUF);
        return new MetricExporter($transport);
    }

    public function createCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Counter
    {
        $counter = $this->meter->createCounter($name, $unit, $description, $advisory);
        return new class ($counter) extends Counter {
            public function __construct(private CounterInterface $counter)
            {
            }
            public function add(float|int $amount, iterable $attributes = []): void
            {
                $this->counter->add($amount, $attributes);
            }
        };
    }

    public function createHistogram(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Histogram
    {
        $histogram = $this->meter->createHistogram($name, $unit, $description, $advisory);
        return new class ($histogram) extends Histogram {
            public function __construct(private HistogramInterface $histogram)
            {
            }
            public function add(float|int $amount, iterable $attributes = []): void
            {
                $this->histogram->record($amount, $attributes);
            }
        };
    }

    public function createGauge(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Gauge
    {
        $gauge = $this->meter->createGauge($name, $unit, $description, $advisory);
        return new class ($gauge) extends Gauge {
            public function __construct(private GaugeInterface $gauge)
            {
            }
            public function add(float|int $amount, iterable $attributes = []): void
            {
                $this->gauge->record($amount, $attributes);
            }
        };
    }

    public function createUpDownCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): UpDownCounter
    {
        $upDownCounter = $this->meter->createUpDownCounter($name, $unit, $description, $advisory);
        return new class ($upDownCounter) extends UpDownCounter {
            public function __construct(private UpDownCounterInterface $upDownCounter)
            {
            }
            public function add(float|int $amount, iterable $attributes = []): void
            {
                $this->upDownCounter->add($amount, $attributes);
            }
        };
    }

    public function collect(): bool
    {
        return $this->reader->collect();
    }
}
