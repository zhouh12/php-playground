<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Simple HTTP request wrapper.
 */
final readonly class Request
{
    public function __construct(
        public string $method,
        public string $uri,
        public array $params = [],
        public array $body = [],
        public array $headers = [],
    ) {
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        
        $body = [];
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $input = file_get_contents('php://input');
            if ($input !== false && $input !== '') {
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }

        return new self(
            method: $method,
            uri: $uri,
            params: $_GET,
            body: $body,
            headers: $headers,
        );
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getBody(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtoupper($key)] ?? $default;
    }
}
