<?php

/**
 * Класс, представляющий полное математическое решение
 * 
 * Реализует JsonSchemaInterface и использует MathStep в своей схеме,
 * демонстрируя вложенность классов с JsonSchemaInterface.
 * 
 * @package Examples\Advanced\Classes
 */

namespace Examples\Advanced\Classes;

use InvalidArgumentException;
use PvSource\Aivory\LLM\Turn\Request\JsonSchemaInterface;

/**
 * Полное математическое решение
 * 
 * Содержит:
 * - Исходную задачу
 * - Массив шагов решения (MathStep)
 * - Финальный ответ
 */
class MathSolution implements JsonSchemaInterface
{
    /**
     * Исходная задача
     * 
     * @var string
     */
    public readonly string $problem;

    /**
     * Массив шагов решения
     * 
     * @var MathStep[]
     */
    public readonly array $steps;

    /**
     * Финальный ответ
     * 
     * @var string
     */
    public readonly string $finalAnswer;

    /**
     * Конструктор
     * 
     * @param string $problem Исходная задача
     * @param MathStep[] $steps Массив шагов решения
     * @param string $finalAnswer Финальный ответ
     */
    public function __construct(
        string $problem,
        array $steps,
        string $finalAnswer
    ) {
        $this->problem = $problem;
        $this->steps = $steps;
        $this->finalAnswer = $finalAnswer;
    }

    /**
     * Возвращает JSON Schema для этого класса
     * 
     * В схеме используется MathStep::class напрямую в поле 'type',
     * что демонстрирует возможность использования классов в схеме.
     * ResponseFormat автоматически вызовет getJsonSchema() у MathStep
     * и заменит класс на его JSON Schema.
     * 
     * Адаптирует схему в зависимости от названия схемы:
     * - Если название 'quick_math_solution' - не включает поле 'steps'
     * - Если название 'advanced_math_solution' или другое - включает поле 'steps'
     * 
     * @param array|null $rootSchema Полная схема запроса от корня.
     *                               Используется для определения названия схемы.
     * @return array JSON Schema массив
     */
    public static function getJsonSchema(?array $rootSchema = null): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'problem' => [
                    'type' => 'string',
                    'description' => 'Исходная математическая задача'
                ],
                'final_answer' => [
                    'type' => 'string',
                    'description' => 'Финальный ответ на задачу'
                ]
            ],
            'required' => ['problem', 'final_answer'],
            'additionalProperties' => false
        ];

        // Определяем, нужно ли включать поле 'steps' на основе названия схемы
        $schemaName = $rootSchema['name'] ?? null;
        $includeSteps = $schemaName !== 'quick_math_solution';

        if ($includeSteps) {
            // Добавляем поле 'steps' для подробных решений
            $schema['properties']['steps'] = [
                'type' => 'array',
                'description' => 'Массив шагов решения задачи',
                'items' => [
                    // Используем класс напрямую - ResponseFormat автоматически
                    // вызовет getJsonSchema() и заменит его на JSON Schema
                    'type' => MathStep::class
                ],
                'minItems' => 1
            ];
            $schema['required'][] = 'steps';
        }

        return $schema;
    }

    /**
     * Создает экземпляр класса из ответа AI
     * 
     * Преобразует данные из ответа AI в типизированный объект MathSolution,
     * включая преобразование массива шагов в массив объектов MathStep.
     * 
     * @param array $data Данные из ответа AI
     * @return static Экземпляр MathSolution
     * 
     * @throws InvalidArgumentException Если данные невалидны или неполны
     */
    public static function fromResponse(array $data): static
    {
        // Проверяем наличие обязательных полей
        $requiredFields = ['problem', 'final_answer'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException(
                    "Отсутствует обязательное поле '{$field}' в данных решения"
                );
            }
        }

        // Проверяем типы
        if (!is_string($data['problem'])) {
            throw new InvalidArgumentException("Поле 'problem' должно быть строкой");
        }

        if (!is_string($data['final_answer'])) {
            throw new InvalidArgumentException("Поле 'final_answer' должно быть строкой");
        }

        // Поле 'steps' опционально (может отсутствовать в quick решениях)
        $steps = [];
        if (isset($data['steps'])) {
            if (!is_array($data['steps'])) {
                throw new InvalidArgumentException("Поле 'steps' должно быть массивом");
            }

            // Преобразуем массив шагов в массив объектов MathStep
            foreach ($data['steps'] as $index => $stepData) {
                try {
                    $steps[] = MathStep::fromResponse($stepData);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException(
                        "Ошибка в шаге #{$index}: " . $e->getMessage()
                    );
                }
            }
        }

        return new self(
            problem: $data['problem'],
            steps: $steps,
            finalAnswer: $data['final_answer']
        );
    }

    /**
     * Возвращает строковое представление решения
     * 
     * @return string
     */
    public function __toString(): string
    {
        $output = "Задача: {$this->problem}\n\n";
        
        if (!empty($this->steps)) {
            $output .= "Решение:\n";
            $output .= str_repeat("=", 50) . "\n";
            
            foreach ($this->steps as $step) {
                $output .= $step . "\n\n";
            }
            
            $output .= str_repeat("=", 50) . "\n";
        }
        
        $output .= "Финальный ответ: {$this->finalAnswer}\n";
        
        return $output;
    }
}

