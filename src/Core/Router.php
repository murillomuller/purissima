<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function resolve(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                // Extract parameters from the path
                $params = $this->extractParams($route['path'], $path);
                $request->setParams($params);
                return $this->callHandler($route['handler'], $request);
            }
        }

        return new Response('Not Found', 404);
    }

    private function matchPath(string $routePath, string $requestPath): bool
    {
        // Simple path matching - can be enhanced with regex for parameters
        if (strpos($routePath, '{') !== false) {
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
            return preg_match('#^' . $pattern . '$#', $requestPath);
        }

        return $routePath === $requestPath;
    }

    private function extractParams(string $routePath, string $requestPath): array
    {
        $params = [];
        
        if (strpos($routePath, '{') !== false) {
            $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $requestPath, $matches)) {
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
            }
        }
        
        return $params;
    }

    private function callHandler(array $handler, Request $request): Response
    {
        [$controllerClass, $method] = $handler;
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            throw new \Exception("Method {$method} not found in {$controllerClass}");
        }

        return $controller->$method($request);
    }
}
