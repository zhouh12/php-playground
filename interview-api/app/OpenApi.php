<?php

declare(strict_types=1);

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Payment API',
    description: 'PHP 8.5 Payment API - Clean Architecture',
    contact: new OA\Contact(name: 'API Support')
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local development server'
)]
#[OA\Tag(
    name: 'Orders',
    description: 'Order management endpoints'
)]
class OpenApi
{
    // This class exists only for OpenAPI annotations
}
