<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\Request;
use Bootstrap\Application;

// Initialize application
$app = new Application(
    databasePath: getenv('DB_DATABASE') ?: 'sqlite:' . __DIR__ . '/../database/database.sqlite'
);

// Run migrations (in production, this would be a separate command)
$app->migrate();

// Capture the incoming request
$request = Request::capture();

// Dispatch to router and send response
$response = $app->getRouter()->dispatch($request);
$response->send();
