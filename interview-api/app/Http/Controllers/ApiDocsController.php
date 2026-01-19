<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use OpenApi\Generator;
use OpenApi\Attributes as OA;

/**
 * API Documentation Controller.
 */
final readonly class ApiDocsController
{
    /**
     * GET /api-docs
     * 
     * Get OpenAPI specification.
     */
    public function spec(Request $request, array $params): JsonResponse
    {
        // __DIR__ is app/Http/Controllers, so go up 3 levels to project root
        $projectRoot = dirname(__DIR__, 3);
        $generator = new Generator();
        $openapi = $generator->generate([
            $projectRoot . '/app',
            $projectRoot . '/bootstrap',
        ]);

        $spec = json_decode($openapi->toJson(), true);

        return JsonResponse::success($spec);
    }

    /**
     * GET /api-docs.json
     * 
     * Get OpenAPI specification as JSON.
     */
    public function json(Request $request, array $params): never
    {
        // __DIR__ is app/Http/Controllers, so go up 3 levels to project root
        $projectRoot = dirname(__DIR__, 3);
        $generator = new Generator();
        $openapi = $generator->generate([
            $projectRoot . '/app',
            $projectRoot . '/bootstrap',
        ]);

        header('Content-Type: application/json');
        echo $openapi->toJson();
        exit;
    }

    /**
     * GET /api-docs.yaml
     * 
     * Get OpenAPI specification as YAML.
     */
    public function yaml(Request $request, array $params): never
    {
        // __DIR__ is app/Http/Controllers, so go up 3 levels to project root
        $projectRoot = dirname(__DIR__, 3);
        $generator = new Generator();
        $openapi = $generator->generate([
            $projectRoot . '/app',
            $projectRoot . '/bootstrap',
        ]);

        header('Content-Type: text/yaml');
        echo $openapi->toYaml();
        exit;
    }

    /**
     * GET /swagger
     * 
     * Serve Swagger UI.
     */
    public function ui(Request $request, array $params): never
    {
        $swaggerUiHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Documentation - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/api-docs.json",
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
HTML;

        header('Content-Type: text/html');
        echo $swaggerUiHtml;
        exit;
    }
}
