<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Simple router for matching HTTP requests to handlers.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): self
    {
        $this->routes[$method][$path] = $handler;
        return $this;
    }

    public function dispatch(Request $request): JsonResponse
    {
        $method = $request->method;
        $uri = $request->uri;

        if (!isset($this->routes[$method])) {
            return JsonResponse::error('Method not allowed', 405);
        }

        foreach ($this->routes[$method] as $path => $handler) {
            $params = $this->matchRoute($path, $uri);
            
            if ($params !== null) {
                return $handler($request, $params);
            }
        }

        return JsonResponse::notFound('Route not found');
    }

    /**
     * Match a route pattern against a URI.
     * 
     * @return array<string, string>|null Route parameters or null if no match
     */
    private function matchRoute(string $pattern, string $uri): ?array
    {
        // Convert route pattern to regex
        // e.g., /api/orders/{orderId}/pay -> /api/orders/(?P<orderId>[^/]+)/pay
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            fn($matches) => "(?P<{$matches[1]}>[^/]+)",
            $pattern
        );
        
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Filter out numeric keys, keep only named captures
            return array_filter(
                $matches,
                fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );
        }

        return null;
    }
}
