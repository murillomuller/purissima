<?php

namespace App\Core;

class Request
{
    private array $params = [];

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        return $path;
    }

    public function getBody(): array
    {
        $body = [];
        
        if ($this->getMethod() === 'GET') {
            $body = $_GET;
        } elseif ($this->getMethod() === 'POST') {
            $body = $_POST;
        }

        // Handle JSON input
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $json = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = array_merge($body, $json);
            }
        }

        return $body;
    }

    public function get(string $key, $default = null)
    {
        return $this->getBody()[$key] ?? $default;
    }

    public function getQuery(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function getFile(string $key): ?array
    {
        if (!$this->hasFile($key)) {
            return null;
        }

        return $_FILES[$key];
    }

    public function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    public function getHeader(string $name): ?string
    {
        $headers = $this->getHeaders();
        return $headers[$name] ?? null;
    }
}
