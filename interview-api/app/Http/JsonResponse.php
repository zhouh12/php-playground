<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Simple JSON response wrapper.
 */
final readonly class JsonResponse
{
    public function __construct(
        public array $data,
        public int $statusCode = 200,
        public array $headers = [],
    ) {
    }

    public function send(): never
    {
        http_response_code($this->statusCode);
        
        header('Content-Type: application/json');
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(array $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400): self
    {
        return new self([
            'success' => false,
            'error' => $message,
        ], $statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return self::error($message, 404);
    }

    public static function serverError(string $message = 'Internal server error'): self
    {
        return self::error($message, 500);
    }
}
