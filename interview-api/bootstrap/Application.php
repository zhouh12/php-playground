<?php

declare(strict_types=1);

namespace Bootstrap;

use App\Application\Contracts\IdGeneratorInterface;
use App\Application\Contracts\PaymentGatewayInterface;
use App\Application\Services\IUserService;
use App\Application\Services\PaymentService;
use App\Application\Services\UserService;
use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Contracts\PaymentRepositoryInterface;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Router;
use App\Infrastructure\Container\Container;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\DatabaseMigrator;
use App\Infrastructure\Payment\StripePaymentGateway;
use App\Infrastructure\Repositories\SqliteOrderRepository;
use App\Infrastructure\Repositories\SqlitePaymentRepository;
use App\Infrastructure\Services\UuidGenerator;
use PDO;

/**
 * Application bootstrap and configuration.
 */
final class Application
{
    private Container $container;
    private Router $router;

    public function __construct(
        private readonly string $databasePath = 'sqlite::memory:',
    ) {
        $this->container = new Container();
        $this->router = new Router();

        $this->registerServices();
        $this->registerRoutes();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Run database migrations.
     */
    public function migrate(): void
    {
        $pdo = $this->container->make(PDO::class);
        $migrator = new DatabaseMigrator($pdo);
        $migrator->migrate();
    }

    private function registerServices(): void
    {
        // Database connection
        $this->container->singleton(PDO::class, fn() => Connection::getInstance($this->databasePath));

        // Domain Repositories
        $this->container->singleton(
            OrderRepositoryInterface::class,
            fn(Container $c) => new SqliteOrderRepository($c->make(PDO::class))
        );

        $this->container->singleton(
            PaymentRepositoryInterface::class,
            fn(Container $c) => new SqlitePaymentRepository($c->make(PDO::class))
        );

        // Application Services / Infrastructure
        $this->container->singleton(
            IdGeneratorInterface::class,
            fn() => new UuidGenerator()
        );

        $this->container->singleton(
            PaymentGatewayInterface::class,
            fn() => new StripePaymentGateway()
        );

        // Application Services
        $this->container->singleton(
            PaymentService::class,
            fn(Container $c) => new PaymentService(
                $c->make(OrderRepositoryInterface::class),
                $c->make(PaymentRepositoryInterface::class),
                $c->make(PaymentGatewayInterface::class),
                $c->make(IdGeneratorInterface::class),
            )
        );

        $this->container->singleton(
            IUserService::class,
            fn() => new UserService()
        );


        // Http Controllers
        $this->container->singleton(
            OrderController::class,
            fn(Container $c) => new OrderController(
                $c->make(PaymentService::class),
                $c->make(OrderRepositoryInterface::class),
            )
        );

        $this->container->singleton(
            UserController::class,
            fn(Container $c) => new UserController(
                $c->make(IUserService::class)            
            )   
        );
    }

    private function registerRoutes(): void
    {
        $orderController = $this->container->make(OrderController::class);
        $userController = $this->container->make(UserController::class);
        $apiDocsController = new ApiDocsController();

        // Root route - API information
        $this->router->get('/', function ($request, $params) {
            return \App\Http\JsonResponse::success([
                'name' => 'Payment API',
                'version' => '1.0.0',
                'description' => 'PHP 8.5 Payment API - Clean Architecture',
                'documentation' => [
                    'swagger_ui' => '/swagger',
                    'openapi_json' => '/api-docs.json',
                    'openapi_yaml' => '/api-docs.yaml',
                ],
                'endpoints' => [
                    'GET /api/orders' => 'List all orders',
                    'GET /api/orders/{orderId}' => 'Get order details',
                    'POST /api/orders/{orderId}/pay' => 'Process payment for an order',
                ],
            ]);
        });

        // API Documentation routes (C# style: /swagger/index.html)
        $this->router->get('/swagger', $apiDocsController->ui(...));
        $this->router->get('/swagger/index.html', $apiDocsController->ui(...));
        $this->router->get('/api-docs', $apiDocsController->spec(...));
        $this->router->get('/api-docs.json', $apiDocsController->json(...));
        $this->router->get('/api-docs.yaml', $apiDocsController->yaml(...));

        // API routes
        $this->router->get('/api/orders', $orderController->index(...));
        $this->router->get('/api/orders/{orderId}', $orderController->show(...));
        $this->router->post('/api/orders/{orderId}/pay', $orderController->pay(...));
        $this->router->get('/api/users/{userId}', $userController->user(...));
    }
}
