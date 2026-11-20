<?php

class Database
{
    public function connect(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_NAME') ?: 'student_management';
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD');

        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $user,
            $password === false ? '' : $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
