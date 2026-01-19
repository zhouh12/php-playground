# PHP 8.5 Payment API - Clean Architecture

A clean, production-ready PHP 8.5 REST API demonstrating Clean Architecture principles for technical interviews.

## Clean Architecture Structure

```
interview-api/
├── app/
│   ├── Domain/                          # Core business logic (innermost layer)
│   │   ├── Models/
│   │   │   ├── Order.php               # Order entity
│   │   │   └── Payment.php             # Payment entity
│   │   ├── Enums/
│   │   │   ├── OrderStatus.php         # Order status enum
│   │   │   └── PaymentStatus.php       # Payment status enum
│   │   ├── Exceptions/
│   │   │   ├── OrderNotFoundException.php
│   │   │   └── PaymentException.php
│   │   └── Contracts/                   # Repository interfaces
│   │       ├── OrderRepositoryInterface.php
│   │       └── PaymentRepositoryInterface.php
│   │
│   ├── Application/                     # Use cases & application services
│   │   ├── Services/
│   │   │   └── PaymentService.php      # Payment processing use case
│   │   ├── DTO/
│   │   │   └── PaymentResponse.php     # Response data transfer object
│   │   └── Contracts/                   # External service interfaces
│   │       ├── PaymentGatewayInterface.php
│   │       ├── PaymentResultInterface.php
│   │       └── IdGeneratorInterface.php
│   │
│   ├── Infrastructure/                  # External concerns & implementations
│   │   ├── Database/
│   │   │   ├── Connection.php
│   │   │   └── DatabaseMigrator.php
│   │   ├── Repositories/
│   │   │   ├── SqliteOrderRepository.php
│   │   │   └── SqlitePaymentRepository.php
│   │   ├── Payment/
│   │   │   ├── PaymentResult.php
│   │   │   └── StripePaymentGateway.php
│   │   ├── Services/
│   │   │   └── UuidGenerator.php
│   │   └── Container/
│   │       └── Container.php           # DI container
│   │
│   └── Http/                           # Web/API layer
│       ├── Controllers/
│       │   └── OrderController.php
│       ├── Request.php
│       ├── Response.php
│       └── Router.php
│
├── bootstrap/
│   └── Application.php                  # Application bootstrap & DI config
│
├── public/
│   └── index.php                        # Entry point
│
└── tests/
    ├── TestCase.php
    ├── Feature/
    │   └── PaymentEndpointTest.php
    └── Unit/
        ├── OrderModelTest.php
        ├── PaymentModelTest.php
        └── PaymentServiceTest.php
```

## Clean Architecture Layers

### 1. Domain Layer (Innermost)
The heart of the application containing business logic that is independent of any external concerns.

- **Models**: Core business entities (`Order`, `Payment`)
- **Enums**: Type-safe status values with business behavior
- **Exceptions**: Domain-specific error handling
- **Contracts**: Repository interfaces (Dependency Inversion)

**Key Rule**: Domain has NO dependencies on other layers.

### 2. Application Layer
Contains application-specific business rules and orchestrates the flow of data.

- **Services**: Use cases like `PaymentService` that coordinate domain objects
- **DTOs**: Data structures for API responses
- **Contracts**: Interfaces for external services (payment gateways, ID generators)

**Key Rule**: Application depends only on Domain.

### 3. Infrastructure Layer
Implements all external concerns and technical details.

- **Database**: Connection management and migrations
- **Repositories**: Concrete implementations of domain repository interfaces
- **Payment**: Payment gateway implementations
- **Services**: Technical utilities (UUID generation)
- **Container**: Dependency injection container

**Key Rule**: Infrastructure implements interfaces defined in Domain/Application.

### 4. Http Layer (Outermost)
Handles HTTP requests and responses, web-specific concerns.

- **Controllers**: Handle HTTP requests, delegate to Application services
- **Request/Response**: HTTP abstraction
- **Router**: URL routing

**Key Rule**: Http depends on Application, never directly on Infrastructure.

## Features

- **PHP 8.5** with modern features (enums, readonly classes, constructor promotion)
- **Clean Architecture** with proper layer separation
- **Dependency Inversion** - all dependencies point inward
- **SOLID Principles** throughout
- **SQLite** for testing (in-memory)
- **PHPUnit 11** for testing

## Requirements

- PHP 8.5+
- Composer 2.x
- SQLite extension (for testing)

## Installation

```bash
cd interview-api
composer install
```

## Running Tests

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only feature tests
composer test:feature

# Run with verbose output
./vendor/bin/phpunit --testdox
```

## API Endpoints

### POST /api/orders/{orderId}/pay

Process payment for an order.

**Success Response (200):**
```json
{
    "success": true,
    "message": "Payment processed successfully",
    "order": {
        "id": "order-123",
        "amount": 5000,
        "currency": "USD",
        "customer_email": "customer@example.com",
        "status": "paid",
        "paid_at": "2024-01-15 10:30:00",
        "created_at": "2024-01-15 09:00:00"
    },
    "payment": {
        "id": "pay-uuid-123",
        "order_id": "order-123",
        "amount": 5000,
        "currency": "USD",
        "status": "completed",
        "gateway_transaction_id": "ch_abc123",
        "gateway_name": "stripe",
        "created_at": "2024-01-15 10:30:00"
    }
}
```

### GET /api/orders/{orderId}

Get order details.

### GET /api/orders

List all orders.

## Running the Server

```bash
php -S localhost:8000 -t public
```

## Adding New Payment Gateways (Open-Closed Principle)

The codebase follows Clean Architecture, making it easy to add new features:

```php
// 1. Create new gateway in Infrastructure layer
namespace App\Infrastructure\Payment;

final readonly class PayPalPaymentGateway implements PaymentGatewayInterface
{
    public function charge(Order $order): PaymentResultInterface
    {
        // PayPal-specific implementation
    }

    public function getName(): string
    {
        return 'paypal';
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array($currency, ['USD', 'EUR'], true);
    }
}

// 2. Update binding in bootstrap/Application.php
$this->container->singleton(
    PaymentGatewayInterface::class,
    fn() => new PayPalPaymentGateway()
);
```

No changes to Domain or Application layers required!

## Dependency Flow

```
Http → Application → Domain
         ↓
    Infrastructure (implements Domain/Application interfaces)
```

All dependencies point toward the Domain layer (Dependency Rule).

## Interview Discussion Points

1. **Why Clean Architecture?**
   - Separates business logic from technical concerns
   - Makes the codebase testable and maintainable
   - Allows swapping implementations without touching business logic

2. **Why Domain at the center?**
   - Business rules are the most stable part
   - Framework/database changes don't affect domain logic
   - Domain can be tested in complete isolation

3. **Why interfaces in Domain/Application?**
   - Dependency Inversion Principle
   - Domain defines what it needs, Infrastructure provides it
   - Enables testing with mocks

4. **How to add features safely?**
   - New use cases in Application layer
   - New implementations in Infrastructure
   - Domain changes only for new business rules
