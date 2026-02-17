<?php

/**
 * Продвинутый пример использования библиотеки Aivory
 *
 * Этот пример демонстрирует:
 * - Использование JsonSchemaInterface для создания типизированных классов
 * - Использование классов в ResponseFormat::jsonSchema() вместо ручного написания схем
 * - Создание кастомного класса Turn с scope методами
 * - Преобразование ответов AI в типизированные объекты через fromResponse()
 * - Адаптивные схемы в зависимости от контекста (название схемы)
 * - Отладочную информацию (схемы, запросы, ответы)
 * - Два режима работы: упрощенное решение (по умолчанию) и подробное (через scope)
 *
 * Перед запуском:
 * 1. Убедитесь, что в .env указаны PROVIDER и API_KEY
 * 2. Установите зависимости: composer install
 * 3. Запустите: php examples/advanced/index.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../_required/load_env.php';

// Подключаем классы примера
require_once __DIR__ . '/classes/MathStep.php';
require_once __DIR__ . '/classes/MathSolution.php';
require_once __DIR__ . '/AdvancedMathTurn.php';

use Examples\Advanced\Classes\MathSolution;
use Examples\Advanced\MathTurn;
use PvSource\Aivory\Client;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;

// ============================================
// Загрузка конфигурации из .env
// ============================================

$provider = $env['PROVIDER'] ?? 'deepseek';
$apiKey = $env['API_KEY'] ?? '';

if (empty($apiKey)) {
    die("Ошибка: API_KEY не указан в .env файле\n");
}

// ============================================
// Инициализация
// ============================================

echo str_repeat("=", 80) . "\n";
echo "ПРОДВИНУТЫЙ ПРИМЕР ИСПОЛЬЗОВАНИЯ AIVORY\n";
echo str_repeat("=", 80) . "\n\n";

echo "Провайдер: {$provider}\n";
echo "Модель: deepseek-chat\n\n";

$client = new Client(
    providerName: $provider,
    auth: ['apiKey' => $apiKey]
);

$providerInstance = $client->getProvider();

// ============================================
// Демонстрация: Создание схемы с использованием классов
// ============================================

echo str_repeat("-", 80) . "\n";
echo "ОТЛАДКА: Создание ResponseFormat с использованием классов\n";
echo str_repeat("-", 80) . "\n\n";

// Создаем ResponseFormat, используя класс MathSolution напрямую
// ResponseFormat автоматически вызовет getJsonSchema() у MathSolution
$responseFormat = ResponseFormat::jsonSchema(
    schema: [
        'type' => MathSolution::class  // Используем класс вместо ручной схемы!
    ],
    name: 'math_solution',
    strict: true
);

echo "1. Исходная схема (как передана в jsonSchema()):\n";
echo "   type: " . MathSolution::class . "\n\n";

// Получаем обработанную схему (после замены классов на JSON Schema)
$processedSchema = $responseFormat->toArray()['schema'];

echo "2. Обработанная схема (после замены классов на JSON Schema):\n";
echo json_encode($processedSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================
// ПРИМЕР 1: Упрощенное решение (по умолчанию, без scope)
// ============================================

$problem = "Реши пример: (15 + 7) * 3 - 12 / 4";

echo str_repeat("=", 80) . "\n";
echo "ПРИМЕР 1: Упрощенное решение (по умолчанию, без scope)\n";
echo str_repeat("=", 80) . "\n\n";

echo "Задача: {$problem}\n\n";

try {
    // Используем AdvancedMathTurn БЕЗ scope метода - будут использованы default значения
    // Это приведет к упрощенному решению без шагов (quick_math_solution)
    echo "Отправка запроса с упрощенным решением (default значения)...\n\n";
    
    $turn1 = MathTurn::query()
        ->withProvider($providerInstance)
        // НЕ вызываем mathSolver() - используем default значения
        ->setUserPrompt($problem)
        ->send();

    echo "Запрос успешно выполнен!\n\n";

    // Получаем ответ и используем новые методы библиотеки
    $response1 = $turn1->getResponse();
    
    // Получаем сообщение от ассистента через новый метод
    $assistantMessage1 = $response1->getAssistant();
    
    // Получаем объект напрямую через getSchemaObjects()
    $mathSolution1 = $response1->getSchemaObjects();

    echo "Результат упрощенного решения:\n";
    echo str_repeat("-", 80) . "\n";
    echo $mathSolution1 . "\n";
    echo "Количество шагов: " . count($mathSolution1->steps) . " (ожидается 0 для упрощенного решения)\n\n";
    
    // Отладочная информация: Usage (использование токенов)
    $rawBody1 = $response1->getRawBody();
    if (isset($rawBody1['usage'])) {
        $usage1 = $rawBody1['usage'];
        echo "ОТЛАДКА: Использование токенов:\n";
        echo str_repeat("-", 80) . "\n";
        echo "  Prompt tokens: " . ($usage1['prompt_tokens'] ?? 'N/A') . "\n";
        echo "  Completion tokens: " . ($usage1['completion_tokens'] ?? 'N/A') . "\n";
        echo "  Total tokens: " . ($usage1['total_tokens'] ?? 'N/A') . "\n\n";
    }

} catch (\Exception $e) {
    echo "Ошибка при выполнении упрощенного запроса:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

// ============================================
// ПРИМЕР 2: Подробное решение (через scope метод)
// ============================================

echo str_repeat("=", 80) . "\n";
echo "ПРИМЕР 2: Подробное решение \n";
echo str_repeat("=", 80) . "\n\n";

echo "Задача: {$problem}\n\n";

try {
    // Используем AdvancedMathTurn с scope методом mathSolver()
    // Это приведет к подробному решению со шагами (advanced_math_solution)
    echo "Отправка запроса с подробным решением...\n\n";
    
    $turn2 = MathTurn::query()
        ->withProvider($providerInstance)
        ->advancedMathSolver()  // Вызывает scopeMathSolver() у AdvancedMathTurn
        ->setUserPrompt($problem)
        ->send();

    echo "Запрос успешно выполнен!\n\n";

    // Получаем ответ и используем новые методы библиотеки
    $response2 = $turn2->getResponse();
    
    // Получаем сообщение от ассистента через новый метод
    $assistantMessage2 = $response2->getAssistant();
    
    // Получаем объект напрямую через getSchemaObjects()
    $mathSolution2 = $response2->getSchemaObjects();

    echo "Результат подробного решения:\n";
    echo str_repeat("-", 80) . "\n";
    echo $mathSolution2 . "\n";
    echo "Количество шагов: " . count($mathSolution2->steps) . " (ожидается > 0 для подробного решения)\n\n";
    
    // Отладочная информация: Reasoning Content (размышление, если включен isThinking)
    if ($assistantMessage2->reasoningContent !== null) {
        echo "ОТЛАДКА: Размышление AI (reasoning_content):\n";
        echo str_repeat("-", 80) . "\n";
        echo $assistantMessage2->reasoningContent . "\n\n";
    } else {
        // Проверяем, был ли включен isThinking в запросе
        $request2 = $turn2->getRequest();
        $isThinking = $request2->getOptions()->isThinking ?? false;
        if ($isThinking) {
            echo "ОТЛАДКА: Режим thinking включен, но reasoning_content отсутствует в ответе. Обратите внимание, что не все провайдеры поддерживают этот режим\n";
        }
    }
    
    // Отладочная информация: Usage (использование токенов)
    $rawBody2 = $response2->getRawBody();
    if (isset($rawBody2['usage'])) {
        $usage2 = $rawBody2['usage'];
        echo "ОТЛАДКА: Использование токенов:\n";
        echo str_repeat("-", 80) . "\n";
        echo "  Prompt tokens: " . ($usage2['prompt_tokens'] ?? 'N/A') . "\n";
        echo "  Completion tokens: " . ($usage2['completion_tokens'] ?? 'N/A') . "\n";
        echo "  Total tokens: " . ($usage2['total_tokens'] ?? 'N/A') . "\n\n";
    }

    // Детальная информация о шагах
    if (!empty($mathSolution2->steps)) {
        echo "Детальная информация о шагах:\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($mathSolution2->steps as $step) {
            echo "Шаг #{$step->stepNumber}:\n";
            echo "  Описание: {$step->description}\n";
            echo "  Вычисление: {$step->calculation}\n";
            echo "  Результат: {$step->result}\n\n";
        }
    }

} catch (\Exception $e) {
    echo "Ошибка при выполнении подробного запроса:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

// ============================================
// Отладочная информация: Сравнение схем
// ============================================

echo str_repeat("=", 80) . "\n";
echo "ОТЛАДКА: Сравнение схем для разных режимов\n";
echo str_repeat("=", 80) . "\n\n";

// Схема для упрощенного решения
$quickFormat = ResponseFormat::jsonSchema(
    schema: ['type' => MathSolution::class],
    name: 'quick_math_solution',
    strict: true
);

// Схема для подробного решения
$advancedFormat = ResponseFormat::jsonSchema(
    schema: ['type' => MathSolution::class],
    name: 'advanced_math_solution',
    strict: true
);

echo "1. Схема для упрощенного решения (quick_math_solution):\n";
echo json_encode($quickFormat->toArray()['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "2. Схема для подробного решения (advanced_math_solution):\n";
echo json_encode($advancedFormat->toArray()['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

echo str_repeat("=", 80) . "\n";
echo "Пример завершен успешно!\n";
echo str_repeat("=", 80) . "\n";
