<?php

namespace App\Core;

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Services\LoggerService;

class Application
{
    private Container $container;
    private LoggerService $logger;

    public function __construct()
    {
        $this->container = new Container();
        $this->logger = new LoggerService();
        $this->registerServices();
    }

    private function registerServices(): void
    {
        $this->container->bind(LoggerService::class, function () {
            return new LoggerService();
        });
    }

    public function run(Router $router): void
    {
        try {
            $request = new Request();
            $response = $router->resolve($request);
            $response->send();
        } catch (\Exception $e) {
            $this->logger->error('Application error: ' . $e->getMessage());
            $this->handleError($e);
        }
    }

    private function handleError(\Exception $e): void
    {
        http_response_code(500);
        
        if ($_ENV['APP_DEBUG'] ?? false) {
            echo '<h1>Error</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            echo '<h1>Internal Server Error</h1>';
            echo '<p>Something went wrong. Please try again later.</p>';
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
