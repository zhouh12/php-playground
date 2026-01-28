<?php

class UserService
{
    private $db;

    public function __construct()
    {
        $this->db = new PDO(
            'sqlite:' . __DIR__ . '/../../database.sqlite'
        );
    }

    public function create($email, $password)
    {
        echo "Creating user $email\n";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email\n";
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            echo "User exists\n";
            return false;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password) VALUES (?, ?)"
        );
        $stmt->execute([$email, $hash]);

        echo "User created\n";

        return $this->db->lastInsertId();
    }
}
