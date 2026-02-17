<?php

/**
 * Загрузка переменных окружения из .env файла
 * 
 * Этот файл должен быть подключен в начале каждого примера.
 * Он загружает переменные из .env файла и делает их доступными через $_ENV и getenv().
 * 
 * @return array Массив с загруженными переменными окружения
 * 
 * @throws RuntimeException Если .env файл не найден
 */
function loadEnv(string $filePath): array
{
    $env = [];
    
    if (!file_exists($filePath)) {
        throw new RuntimeException("Файл .env не найден: {$filePath}");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Пропускаем комментарии
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Парсим KEY=VALUE
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Удаляем кавычки, если они есть
            if ((strpos($value, '"') === 0 && substr($value, -1) === '"') ||
                (strpos($value, "'") === 0 && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $env[$key] = $value;
            
            // Устанавливаем переменные окружения
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    return $env;
}

// Автоматически загружаем .env из корня проекта
$envPath = __DIR__ . '/../../.env';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../.env';
}

if (file_exists($envPath)) {
    $env = loadEnv($envPath);
} else {
    throw new RuntimeException("Файл .env не найден. Проверьте наличие файла в корне проекта.");
}

