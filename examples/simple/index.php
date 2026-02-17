<?php

/**
 * Простой пример использования библиотеки Aivory
 *
 * Этот пример демонстрирует базовое использование библиотеки для отправки
 * запроса к AI провайдеру (DeepSeek, OpenAI и т.д.)
 *
 * Перед запуском:
 * 1. Убедитесь, что в .env указаны PROVIDER и API_KEY
 * 2. Установите зависимости: composer install
 * 3. Запустите: php examples/simple/index.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../_required/load_env.php';

use PvSource\Aivory\Client;
use PvSource\Aivory\LLM\Turn\Turn;

// ============================================
// Загрузка конфигурации из .env
// ============================================

$provider = $env['PROVIDER'] ?? 'deepseek';
$apiKey = $env['API_KEY'] ?? '';

if (empty($apiKey)) {
    die("Ошибка: API_KEY не указан в .env файле\n");
}

// ============================================
// Создание клиента
// ============================================

echo "========================================\n";
echo "Простой пример использования Aivory\n";
echo "========================================\n\n";
echo "Провайдер: {$provider}\n";
echo "Модель: deepseek-chat\n\n";

$client = new Client(
    providerName: $provider,
    auth: ['apiKey' => $apiKey]
);

$providerInstance = $client->getProvider();

// ============================================
// Отправка простого запроса
// ============================================

echo "Отправка запроса...\n";
echo "Вопрос: Как готовить блины?\n\n";

try {
    // Используем базовый класс Turn для простого запроса
    $turn = Turn::query()
        ->withProvider($providerInstance)
        ->setSystemPrompt('Ты эксперт по приготовлению блюд, помоги человеку с рецептом.')
        ->setUserPrompt('Как готовить блины?')
        ->setModel('deepseek-chat')
        ->setTemperature(0.7)
        ->setMaxTokens(500)
        ->send();
    
    echo "Запрос успешно выполнен!\n\n";
    
    // ============================================
    // Вывод результатов
    // ============================================
    
    echo "========================================\n";
    echo "Ответ от AI:\n";
    echo "========================================\n\n";
    
    $response = $turn->getResponse();
    
    // Выводим сообщения из ответа
    foreach ($response->getMessages() as $message) {
        echo "[{$message->role}]: {$message->content}\n\n";
    }
    
    echo "========================================\n";
    echo "Пример завершен успешно!\n";
    echo "========================================\n";
    
} catch (\Exception $e) {
    echo "Ошибка при выполнении запроса:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

