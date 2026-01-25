<?php
declare(strict_types=1);
namespace Tests\Unit;

use Tests\TestCase;
use App\Application\DTO\UserResponse;
use App\Http\Controllers\UserController;
use App\Application\Services\IUserService;
use App\Http\Request;

use function PHPUnit\Framework\assertJson;

final class UserControllerTest extends TestCase
{
    public function test_user_returns_success_response(): void
    {
        // Arrange
        $userService = $this->createMock(IUserService::class);

        $userDto = new UserResponse(
            'guid-123',
            'Jimmy',
            'Zhou'
        );

        $userService
            ->expects($this->once())
            ->method('getUserById')
            ->with('guid-123')
            ->willReturn($userDto);

        $controller = new UserController($userService);

        $request = new Request('GET', 'api/users/guid-123');

        $params = ['userId' => 'guid-123'];
            
        // Act
        $response = $controller->user($request, $params);

        // Assert
        $this->assertEquals(200, $response->statusCode);

        $data = $response->data;

        $this->assertTrue($data['success']);
        $this->assertSame($userDto, $data['user']);
    }
}