<?php
namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][] = [
            'path'    => rtrim($path, '/') ?: '/',
            'handler' => $handler,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri    = rtrim($uri, '/') ?: '/';
        $method = strtoupper($method);

        foreach ($this->routes[$method] ?? [] as $route) {
            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    fn ($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY
                );

                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$class, $methodName] = $handler;
                    $controller = new $class();
                    $controller->$methodName(...$params);
                } else {
                    $handler(...$params);
                }

                // ya atendimos la ruta, salimos del método
                return;
            }
        }

        http_response_code(404);
        echo 'Página no encontrada';
    }
}
