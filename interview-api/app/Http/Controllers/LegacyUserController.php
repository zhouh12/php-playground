<?php
declare(strict_types=1);

class LegacyUserController
{
    public function createUser()
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            echo json_encode(['error' => 'Invalid JSON']);
            http_response_code(400);
            return;
        }

        if (empty($body['email']) || empty($body['password'])) {
            echo json_encode(['error' => 'Missing fields']);
            http_response_code(400);
            return;
        }

        $service = new UserService();
        $result = $service->create(
            $body['email'],
            $body['password']
        );

        if ($result === false) {
            echo json_encode(['error' => 'User already exists']);
            http_response_code(409);
            return;
        }

        echo json_encode([
            'status' => 'ok',
            'userId' => $result
        ]);
    }
}
