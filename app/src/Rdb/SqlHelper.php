<?php

namespace App\Rdb;

use mysqli;
use RuntimeException;

// SqlHelper - класс для работы с подключением к БД
class SqlHelper
{
    public function __construct()
    {
        // Вызвать метод pingDb в конструкторе
        $this->pingDb();
    }

    // openDbConnection - создает соединение с БД согласно параметрам подключения
    public function openDbConnection(): mysqli
    {
        // Получить параметры подключения из переменных окружения $_ENV
        $host = $_ENV['DB_HOST'] ?? 'localhost'; 
        $port = (int) ($_ENV['DB_PORT'] ?? 3306);
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? 'root';
        $database = $_ENV['DB_NAME'] ?? 'world_countries_db';

        // Создать объект подключения через драйвер mysqli
        $connection = new mysqli(
            hostname: $host,
            port: $port,
            username: $user,
            password: $password,
            database: $database,
        );

        // Проверить, возникли ли ошибки при подключении
        if ($connection->connect_errno) {
            throw new RuntimeException(message: "Failed to connect to MySQL: " . $connection->connect_error);
        }

        // Если всё ок - вернуть соединение с БД
        return $connection;
    }

    // pingDb - проверяет доступность БД путем создания подключения и его закрытия
    public function pingDb(): void // <-- Сделать его public
    {
        // Открыть и закрыть соединение с БД
        $connection = $this->openDbConnection();
        // Не используем встроенный ping, а просто открываем и закрываем
        $connection->close();
    }
}