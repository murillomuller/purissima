<?php

namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class LoggerService
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('app');
        
        // Create logs directory if it doesn't exist
        $logPath = __DIR__ . '/../../storage/logs';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        // Add rotating file handler
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath . '/app.log', 0, Logger::DEBUG)
        );
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
