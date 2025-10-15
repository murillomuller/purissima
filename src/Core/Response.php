<?php

namespace App\Core;

class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): self
    {
        $this->content = json_encode($data);
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json');
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->content = '';
        $this->statusCode = $statusCode;
        $this->setHeader('Location', $url);
        return $this;
    }

    public function file(string $filePath, string $filename = null): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $this->content = file_get_contents($filePath);
        $this->statusCode = 200;
        $this->setHeader('Content-Type', mime_content_type($filePath));
        $this->setHeader('Content-Disposition', 'attachment; filename="' . ($filename ?? basename($filePath)) . '"');
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }
}
