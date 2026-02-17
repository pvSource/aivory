<?php

/**
 * Продвинутый класс Turn для математических задач
 * 
 * Наследует Turn и демонстрирует:
 * - Использование scope методов
 * - Настройку ResponseFormat с использованием классов JsonSchemaInterface
 * - Установку значений по умолчанию через protected свойства
 * 
 * @package Examples\Advanced
 */

namespace Examples\Advanced;

use Examples\Advanced\Classes\MathSolution;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;
use PvSource\Aivory\LLM\Turn\Turn;
use PvSource\Aivory\LLM\Turn\TurnBuilder;

/**
 * Продвинутый Turn для математических задач
 * 
 * Использует MathSolution (реализующий JsonSchemaInterface) в ResponseFormat,
 * что позволяет получать структурированные ответы и преобразовывать их
 * в типизированные объекты.
 * 
 * Демонстрирует:
 * - Переопределение default значений через protected static свойства (как в Eloquent)
 * - Использование scope методов для разных режимов работы
 * - Адаптивные схемы в зависимости от контекста
 * 
 * По умолчанию использует упрощенное решение (quick_math_solution) без шагов.
 * Для подробного решения используйте scope метод mathSolver().
 */
class MathTurn extends Turn
{
    /**
     * Системный промпт по умолчанию для быстрого решения
     * 
     * @var string|null
     */
    protected static ?string $defaultSystemPrompt = 'Ты математический калькулятор. Решай задачи быстро и точно.';

    /**
     * Модель по умолчанию
     * 
     * @var string|null
     */
    protected static ?string $defaultModel = 'deepseek-chat';

    /**
     * Температура по умолчанию (очень низкая для детерминированных ответов)
     * 
     * @var float|null
     */
    protected static ?float $defaultTemperature = 0.1;

    /**
     * Максимальное количество токенов по умолчанию
     * 
     * @var int|null
     */
    protected static ?int $defaultMaxTokens = 1000;

    /**
     * JSON Schema для упрощенного решения (без шагов)
     * 
     * @var array|null
     */
    protected static ?array $defaultResponseJsonSchema = [
        'type' => MathSolution::class
    ];

    /**
     * Название схемы ResponseFormat по умолчанию
     * 
     * Название 'quick_math_solution' приведет к тому, что
     * MathSolution::getJsonSchema() не включит поле 'steps' в схему.
     * 
     * @var string|null
     */
    protected static ?string $defaultResponseFormatName = 'quick_math_solution';

    /**
     * Scope метод для настройки подробного математического решателя
     * 
     * Этот метод будет вызываться через TurnBuilder::mathSolver().
     * Настраивает системный промпт, параметры модели и ResponseFormat
     * с использованием класса MathSolution.
     * 
     * Использует название схемы 'advanced_math_solution', что приводит к тому,
     * что MathSolution::getJsonSchema() включит поле 'steps' в схему.
     * 
     * @param TurnBuilder $builder Билдер для настройки запроса
     * @return void
     */
    public static function scopeAdvancedMathSolver(TurnBuilder $builder): void
    {
        // Устанавливаем системный промпт для подробного решения
        $builder->setSystemPrompt(
            'Ты эксперт по математике. Решай задачи пошагово, объясняя каждый шаг. ' .
            'Используй четкие вычисления и логические рассуждения.'
        );

        // Включаем режим thinking
        $builder->setIsThinking(true);

        // Устанавливаем температуру (низкая для более детерминированных ответов)
        $builder->setTemperature(0.3);

        // Устанавливаем максимальное количество токенов (больше для подробных решений)
        $builder->setMaxTokens(2000);

        // Создаем ResponseFormat с использованием класса MathSolution
        // Название схемы 'advanced_math_solution' приведет к тому, что
        // MathSolution::getJsonSchema() включит поле 'steps' в схему
        $responseFormat = ResponseFormat::jsonSchema(
            schema: [
                // Используем класс напрямую - ResponseFormat автоматически
                // обработает его и заменит на JSON Schema
                'type' => MathSolution::class
            ],
            name: 'advanced_math_solution',  // Важно: это название определяет, будет ли поле 'steps'
            strict: true
        );

        $builder->setResponseFormat($responseFormat);
    }
}
