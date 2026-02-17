<?php

/**
 * Класс, представляющий один шаг математического решения
 * 
 * Реализует JsonSchemaInterface, что позволяет использовать его напрямую
 * в ResponseFormat::jsonSchema() вместо написания JSON Schema вручную.
 * 
 * @package Examples\Advanced\Classes
 */

namespace Examples\Advanced\Classes;

use InvalidArgumentException;
use PvSource\Aivory\LLM\Turn\Request\JsonSchemaInterface;

/**
 * Шаг математического решения
 * 
 * Содержит информацию об одном шаге решения математической задачи:
 * - Номер шага
 * - Описание того, что делается на этом шаге
 * - Вычисление (формула или выражение)
 * - Результат шага
 */
class MathStep implements JsonSchemaInterface
{
    /**
     * Номер шага (начинается с 1)
     * 
     * @var int
     */
    public readonly int $stepNumber;

    /**
     * Описание шага (что делается на этом этапе)
     * 
     * @var string
     */
    public readonly string $description;

    /**
     * Вычисление (формула или выражение, которое выполняется)
     * 
     * @var string
     */
    public readonly string $calculation;

    /**
     * Результат шага (число или выражение после вычисления)
     * 
     * @var string
     */
    public readonly string $result;

    /**
     * Конструктор
     * 
     * @param int $stepNumber Номер шага
     * @param string $description Описание шага
     * @param string $calculation Вычисление
     * @param string $result Результат шага
     */
    public function __construct(
        int $stepNumber,
        string $description,
        string $calculation,
        string $result
    ) {
        $this->stepNumber = $stepNumber;
        $this->description = $description;
        $this->calculation = $calculation;
        $this->result = $result;
    }

    /**
     * Возвращает JSON Schema для этого класса
     * 
     * Схема описывает структуру данных, которую AI должен вернуть
     * для одного шага математического решения.
     * 
     * Обратите внимание: ключи в схеме используют snake_case (step_number),
     * так как это стандарт для JSON API, но в классе мы используем camelCase (stepNumber).
     * 
     * @param array|null $rootSchema Полная схема запроса от корня (не используется в этом классе)
     * @return array JSON Schema массив
     */
    public static function getJsonSchema(?array $rootSchema = null): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'step_number' => [
                    'type' => 'integer',
                    'description' => 'Номер шага (начинается с 1)',
                    'minimum' => 1
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Описание того, что делается на этом шаге'
                ],
                'calculation' => [
                    'type' => 'string',
                    'description' => 'Вычисление (формула или выражение)'
                ],
                'result' => [
                    'type' => 'string',
                    'description' => 'Результат шага после вычисления'
                ]
            ],
            'required' => ['step_number', 'description', 'calculation', 'result'],
            'additionalProperties' => false
        ];
    }

    /**
     * Создает экземпляр класса из ответа AI
     * 
     * Преобразует данные из ответа AI (массив с ключами в snake_case)
     * в типизированный объект MathStep.
     * 
     * @param array $data Данные из ответа AI
     * @return static Экземпляр MathStep
     * 
     * @throws InvalidArgumentException Если данные невалидны или неполны
     */
    public static function fromResponse(array $data): static
    {
        // Проверяем наличие обязательных полей
        $requiredFields = ['step_number', 'description', 'calculation', 'result'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException(
                    "Отсутствует обязательное поле '{$field}' в данных шага"
                );
            }
        }

        // Проверяем тип step_number
        if (!is_int($data['step_number']) || $data['step_number'] < 1) {
            throw new InvalidArgumentException(
                "Поле 'step_number' должно быть целым числом >= 1"
            );
        }

        // Проверяем, что остальные поля - строки
        foreach (['description', 'calculation', 'result'] as $field) {
            if (!is_string($data[$field])) {
                throw new InvalidArgumentException(
                    "Поле '{$field}' должно быть строкой"
                );
            }
        }

        return new self(
            stepNumber: $data['step_number'],
            description: $data['description'],
            calculation: $data['calculation'],
            result: $data['result']
        );
    }

    /**
     * Возвращает строковое представление шага
     * 
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            "Шаг %d: %s\n  Вычисление: %s\n  Результат: %s",
            $this->stepNumber,
            $this->description,
            $this->calculation,
            $this->result
        );
    }
}

