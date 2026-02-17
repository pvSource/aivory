<?php

namespace PvSource\Aivory\LLM\Turn\Request;

/**
 * Интерфейс для классов, которые могут предоставлять JSON Schema
 * и создавать экземпляры из ответов AI
 * 
 * Позволяет использовать классы бизнес-сущностей напрямую в ResponseFormat::jsonSchema()
 * вместо написания JSON Schema вручную.
 * 
 * @package PvSource\Aivory\LLM\Turn
 * 
 * @example
 * class MathResolverStep implements JsonSchemaInterface
 * {
 *     public function __construct(
 *         public readonly int $stepNumber,
 *         public readonly string $description,
 *         public readonly string $calculation,
 *         public readonly string $result
 *     ) {}
 *     
 *     public static function getJsonSchema(): array
 *     {
 *         return [
 *             'type' => 'object',
 *             'properties' => [
 *                 'step_number' => ['type' => 'integer'],
 *                 'description' => ['type' => 'string'],
 *                 'calculation' => ['type' => 'string'],
 *                 'result' => ['type' => 'string']
 *             ],
 *             'required' => ['step_number', 'description', 'calculation', 'result']
 *         ];
 *     }
 *     
 *     public static function fromResponse(array $data): static
 *     {
 *         return new self(
 *             stepNumber: $data['step_number'],
 *             description: $data['description'],
 *             calculation: $data['calculation'],
 *             result: $data['result']
 *         );
 *     }
 * }
 * 
 * @example
 * // Использование в ResponseFormat
 * ResponseFormat::jsonSchema([
 *     'type' => 'object',
 *     'properties' => [
 *         'steps' => [
 *             'type' => 'array',
 *             'items' => [
 *                 'type' => MathResolverStep::class  // Автоматически вызовет getJsonSchema()
 *             ]
 *         ]
 *     ]
 * ]);
 */
interface JsonSchemaInterface
{
    /**
     * Возвращает JSON Schema для этого класса
     * 
     * Схема должна соответствовать стандарту JSON Schema (draft-07 или новее).
     * В схеме можно использовать другие классы, реализующие JsonSchemaInterface,
     * передавая их в поле 'type' - они будут автоматически обработаны.
     * 
     * @param array|null $rootSchema Полная схема запроса от корня (опционально).
     *                               Может содержать метаданные, например 'name' схемы,
     *                               что позволяет адаптировать схему в зависимости от контекста.
     * 
     * @return array JSON Schema массив
     * 
     * @example
     * // Простая схема без контекста
     * return [
     *     'type' => 'object',
     *     'properties' => [
     *         'step_number' => ['type' => 'integer'],
     *         'description' => ['type' => 'string']
     *     ],
     *     'required' => ['step_number', 'description']
     * ];
     * 
     * @example
     * // Адаптивная схема в зависимости от контекста
     * public static function getJsonSchema(?array $rootSchema = null): array
     * {
     *     $schema = ['type' => 'object', 'properties' => [...]];
     *     
     *     // Если название схемы 'quick_solution', не включаем поле 'steps'
     *     if (isset($rootSchema['name']) && $rootSchema['name'] === 'quick_solution') {
     *         unset($schema['properties']['steps']);
     *         $schema['required'] = array_diff($schema['required'], ['steps']);
     *     }
     *     
     *     return $schema;
     * }
     */
    public static function getJsonSchema(?array $rootSchema = null): array;
    
    /**
     * Создает экземпляр класса из ответа AI
     * 
     * Преобразует данные из ответа AI (обычно это массив с ключами в snake_case)
     * в экземпляр класса с типизированными свойствами.
     * 
     * @param array $data Данные из ответа AI
     * @return static Экземпляр класса
     * 
     * @throws \InvalidArgumentException Если данные невалидны или неполны
     * 
     * @example
     * $data = ['step_number' => 1, 'description' => 'Step 1', ...];
     * $step = MathResolverStep::fromResponse($data);
     */
    public static function fromResponse(array $data): static;
}


