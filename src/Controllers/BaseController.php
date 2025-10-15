<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\LoggerService;

abstract class BaseController
{
    protected LoggerService $logger;

    public function __construct()
    {
        $this->logger = new LoggerService();
    }

    protected function view(string $template, array $data = []): Response
    {
        $viewPath = __DIR__ . '/../../views/' . $template . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$template}");
        }

        // Extract data to variables for the view
        extract($data);

        // Start output buffering
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        return new Response($content);
    }

    protected function json(array $data, int $statusCode = 200): Response
    {
        return (new Response())->json($data, $statusCode);
    }

    protected function redirect(string $url): Response
    {
        return (new Response())->redirect($url);
    }

    protected function file(string $filePath, string $filename = null): Response
    {
        return (new Response())->file($filePath, $filename);
    }
}
