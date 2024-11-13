<?php

namespace Utopia\Telemetry\Logger;

use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\Severity;
use OpenTelemetry\API\Signals;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\Contrib\Otlp\HttpEndpointResolver;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\LogRecordExporterInterface;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\ResourceAttributes;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;

class OpenTelemetry extends Adapter
{
    private LoggerInterface $logger;

    public function __construct(string $endpoint, string $serviceNamespace, string $serviceName, string $serviceInstanceId)
    {
        $exporter = $this->createExporter($endpoint);
        $attributes = Attributes::create([
            'service.namespace' => $serviceNamespace,
            'service.name' => $serviceName,
            'service.instance.id' => $serviceInstanceId,
        ]);

        $this->logger = $this->initLogger($exporter, $attributes);
    }

    protected function initLogger(LogRecordExporterInterface $exporter, AttributesInterface $attributes): LoggerInterface
    {
        $loggerProvider = LoggerProvider::builder()
            ->setResource(ResourceInfo::create($attributes, ResourceAttributes::SCHEMA_URL))
            ->addLogRecordProcessor(new SimpleLogRecordProcessor($exporter))
            ->build();

        return $loggerProvider->getLogger('cloud');
    }

    protected function createExporter(string $endpoint): LogRecordExporterInterface
    {
        $endpoint = HttpEndpointResolver::create()->resolveToString($endpoint, Signals::LOGS);
        $transport = (new OtlpHttpTransportFactory())->create($endpoint, ContentTypes::PROTOBUF);
        return new LogsExporter($transport);
    }

    public static function getName(): string
    {
        return "opentelemetry";
    }

    public function push(Log $log): int
    {
        $breadcrumbsArray = [];
        foreach ($log->getBreadcrumbs() as $breadcrumb) {
            $breadcrumbsArray[] = [
                'type' => 'default',
                'level' => $breadcrumb->getType(),
                'category' => $breadcrumb->getCategory(),
                'message' => $breadcrumb->getMessage(),
                'timestamp' => $breadcrumb->getTimestamp(),
            ];
        }

        $stackFrames = [];
        if (isset($log->getExtra()['detailedTrace'])) {
            $detailedTrace = $log->getExtra()['detailedTrace'];
            if (! is_array($detailedTrace)) {
                throw new \Exception('detailedTrace must be an array');
            }
            foreach ($detailedTrace as $trace) {
                if (! is_array($trace)) {
                    throw new \Exception('detailedTrace must be an array of arrays');
                }
                $stackFrames[] = [
                    'filename' => $trace['file'] ?? '',
                    'lineno' => $trace['line'] ?? 0,
                    'function' => $trace['function'] ?? '',
                ];
            }
        }

        $payload = [
            'message' => $log->getMessage(),
            'environment' => $log->getEnvironment(),
            'server_name' => $log->getServer(),
            'stacktrace' => $stackFrames,
            'tags' => $log->getTags(),
            'extra' => $log->getExtra(),
            'breadcrumbs' => $breadcrumbsArray,
            'user' => empty($log->getUser()) ? null : [
                'id' => $log->getUser()->getId(),
                'email' => $log->getUser()->getEmail(),
                'username' => $log->getUser()->getUsername(),
            ],
        ];

        $record = (new LogRecord())
            ->setTimestamp((int)$log->getTimestamp())
            ->setSeverityNumber(self::mapLogType($log))
            ->setSeverityText($log->getType())
            ->setBody($payload)
            ->setAttribute('logger', $log->getNamespace())
            ->setAttribute('release', $log->getVersion())
            ->setAttribute('transaction', $log->getAction());

        $this->logger->emit($record);
        return 200;
    }

    private static function mapLogType(Log $log): Severity {
        return match ($log->getType()) {
            Log::TYPE_VERBOSE => Severity::TRACE,
            Log::TYPE_DEBUG => Severity::DEBUG,
            Log::TYPE_INFO => Severity::INFO,
            Log::TYPE_WARNING => Severity::WARN,
            Log::TYPE_ERROR => Severity::ERROR,
        };
    }

    public function getSupportedTypes(): array
    {
        return [
            Log::TYPE_INFO,
            Log::TYPE_DEBUG,
            Log::TYPE_WARNING,
            Log::TYPE_ERROR,
            Log::TYPE_VERBOSE,
        ];
    }

    public function getSupportedEnvironments(): array
    {
        return [
            Log::ENVIRONMENT_STAGING,
            Log::ENVIRONMENT_PRODUCTION,
        ];
    }

    public function getSupportedBreadcrumbTypes(): array
    {
        return [
            Log::TYPE_INFO,
            Log::TYPE_DEBUG,
            Log::TYPE_WARNING,
            Log::TYPE_ERROR,
        ];
    }
}