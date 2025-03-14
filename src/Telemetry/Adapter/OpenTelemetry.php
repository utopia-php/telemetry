<?php

namespace Utopia\Telemetry\Adapter;

use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\UpDownCounterInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use OpenTelemetry\SemConv\ResourceAttributes;
use Utopia\Telemetry\Adapter;
use Utopia\Telemetry\Counter;
use Utopia\Telemetry\Gauge;
use Utopia\Telemetry\Histogram;
use Utopia\Telemetry\Span;
use Utopia\Telemetry\UpDownCounter;

class OpenTelemetry implements Adapter
{
    private MetricReaderInterface $metricsReader;
    private SpanProcessorInterface $spanProcessor;
    private MeterInterface $meter;
    private TracerInterface $tracer;
    private array $meterStorage = [
        Counter::class => [],
        UpDownCounter::class => [],
        Histogram::class => [],
        Gauge::class => [],
    ];

    public function __construct(string $metricsEndpoint, string $tracingEndpoint, string $serviceNamespace, string $serviceName, string $serviceInstanceId)
    {
        $attributes = Attributes::create([
            'service.namespace' => $serviceNamespace,
            'service.name' => $serviceName,
            'service.instance.id' => $serviceInstanceId,
        ]);
        $meterProvider = $this->initMeter($metricsEndpoint, $attributes);
        $this->meter = $meterProvider->getMeter('cloud');

        $tracingProvider = $this->initTracer($tracingEndpoint, $attributes);
        $this->tracer = $tracingProvider->getTracer('cloud');

        Sdk::builder()
            ->setMeterProvider($meterProvider)
            ->setTracerProvider($tracingProvider)
            ->buildAndRegisterGlobal();
    }

    protected function initMeter(string $endpoint, AttributesInterface $attributes): MeterProviderInterface
    {
        $transport = (new OtlpHttpTransportFactory())->create($endpoint, ContentTypes::PROTOBUF);
        $exporter = new MetricExporter($transport, Temporality::CUMULATIVE);
        $this->metricsReader = new ExportingReader($exporter);
        return MeterProvider::builder()
            ->setResource(ResourceInfo::create($attributes, ResourceAttributes::SCHEMA_URL))
            ->addReader($this->metricsReader)
            ->build();
    }

    protected function initTracer(string $endpoint, AttributesInterface $attributes): TracerProviderInterface
    {
        $transport = (new OtlpHttpTransportFactory())->create($endpoint, ContentTypes::PROTOBUF);
        $this->spanProcessor = new SimpleSpanProcessor(new SpanExporter($transport));
        return TracerProvider::builder()
            ->setResource(ResourceInfo::create($attributes, ResourceAttributes::SCHEMA_URL))
            ->addSpanProcessor($this->spanProcessor)
            ->build();
    }

    private function createMeter(string $type, string $name, callable $creator): mixed
    {
        if (!isset($this->meterStorage[$type][$name])) {
            $this->meterStorage[$type][$name] = $creator();
        }

        return $this->meterStorage[$type][$name];
    }

    public function createCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Counter
    {
        $counter = $this->createMeter(Counter::class, $name, fn () => $this->meter->createCounter($name, $unit, $description, $advisory));
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
        $histogram = $this->createMeter(Histogram::class, $name, fn () => $this->meter->createHistogram($name, $unit, $description, $advisory));
        return new class ($histogram) extends Histogram {
            public function __construct(private HistogramInterface $histogram)
            {
            }
            public function record(float|int $amount, iterable $attributes = []): void
            {
                $this->histogram->record($amount, $attributes);
            }
        };
    }

    public function createGauge(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): Gauge
    {
        $gauge = $this->createMeter(Gauge::class, $name, fn () => $this->meter->createGauge($name, $unit, $description, $advisory));
        return new class ($gauge) extends Gauge {
            public function __construct(private GaugeInterface $gauge)
            {
            }
            public function record(float|int $amount, iterable $attributes = []): void
            {
                $this->gauge->record($amount, $attributes);
            }
        };
    }

    public function createUpDownCounter(string $name, ?string $unit = null, ?string $description = null, array $advisory = []): UpDownCounter
    {
        $upDownCounter = $this->createMeter(UpDownCounter::class, $name, fn () => $this->meter->createUpDownCounter($name, $unit, $description, $advisory));
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

    public function createSpan(string $name): Span
    {
        $span = $this->tracer->spanBuilder($name)->startSpan();
        return new class($span) extends Span {
            public function __construct(private SpanInterface $span)
            {
            }
            public function end(): void
            {
                $this->span->end();
            }
        };
    }

    public function collect(): bool
    {
        return $this->metricsReader->collect();
    }
}
