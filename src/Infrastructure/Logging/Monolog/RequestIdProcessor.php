<?php

namespace App\Infrastructure\Logging\Monolog;

use App\Infrastructure\Logging\RequestIdProvider;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

#[AsMonologProcessor]
class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestIdProvider $requestIdProvider
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['request_id'] = $this->requestIdProvider->getRequestId();

        return $record;
    }
}
