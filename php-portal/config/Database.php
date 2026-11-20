<?php

class Database
{
    public static function settings(): array
    {
        $local = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
        $defaults = ['host' => '127.0.0.1', 'port' => '3306', 'database' => 'student_management', 'user' => 'root', 'password' => ''];
        $variables = ['host' => 'DB_HOST', 'port' => 'DB_PORT', 'database' => 'DB_NAME', 'user' => 'DB_USER', 'password' => 'DB_PASSWORD'];
        foreach ($variables as $key => $variable) {
            $value = getenv($variable);
            $defaults[$key] = $value === false ? ($local[$key] ?? $defaults[$key]) : $value;
        }
        return $defaults;
    }

    public function connect(?string $database = null): PDO
    {
        $settings = self::settings();
        $name = $database ?? $settings['database'];
        return new PDO(
            'mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';dbname=' . $name . ';charset=utf8mb4',
            $settings['user'],
            $settings['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
}
