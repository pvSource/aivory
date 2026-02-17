<?php

namespace PvSource\Aivory\LLM\Turn\Request;

use InvalidArgumentException;

class ResponseFormat
{
    /**
     * Тип формата: JSON Schema
     */
    public const string TYPE_JSON_SCHEMA = 'json_schema';

    /**
     * Тип формата: текст
     */
    public const string TYPE_TEXT = 'text';

    /**
     * Тип формата ответа.
     *
     * @var string
     * @readonly
     */
    public readonly string $type;

    /**
     * Имя схемы (для идентификации).
     *
     * @var string|null
     * @readonly
     */
    public readonly ?string $name;

    /**
     * Строгий режим валидации.
     *
     * Если true - ответ должен точно соответствовать schema.
     * Если false - допускаются отклонения от schema.
     *
     * @var bool|null
     * @readonly
     */
    public readonly ?bool $strict;

    /**
     * JSON Schema структура ответа.
     *
     * Описывает формат ответа в виде JSON Schema (Draft 7 или совместимый).
     *
     * @var array|null
     * @readonly
     */
    public readonly ?array $schema;

    /**
     * Приватный конструктор.
     *
     * Использовать {@see self::jsonSchema()}  для создания экземпляра.
     *
     * @param string $type Тип формата
     * @param array|null $schema JSON Schema структура, опционально для типа формата: JSON Schema
     * @param string|null $name Имя схемы, опционально для типа формата: JSON Schema
     * @param bool|null $strict Строгий режим, опционально для типа формата: JSON Schema
     */
    private function __construct(
        string $type,
        ?array $schema = null,
        ?string $name = null,
        ?bool $strict = true
    ) {
        if (!in_array(
            needle: $type,
            haystack: [self::TYPE_JSON_SCHEMA, self::TYPE_TEXT],
            strict: true)
        ) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        $this->type = $type;
        $this->schema = $schema;
        $this->name = $name;
        $this->strict = $strict;
    }

    /**
     * Создает формат с JSON schema.
     *
     * @param array $schema JSON Schema структура.
     * @param string|null $name Имя схемы (опционально, для идентификации)
     * @param bool|null $strict Строгий режим валидации (по умолчанию true)
     *
     * @return self
     *
     * @throws InvalidArgumentException Если schema пустая или некорректна
     *
     * @example
     * $format = AnswerFormat::jsonSchema(
     *     schema: [
     *         'type' => 'object',
     *         'properties' => [
     *             'steps' => [
     *                 'type' => 'array',
     *                 'items' => [
     *                     'type' => 'object',
     *                     'properties' => [
     *                         'explanation' => ['type' => 'string'],
     *                         'output' => ['type' => 'string']
     *                     ],
     *                     'required' => ['explanation', 'output']
     *                 ]
     *             ],
     *             'final_answer' => ['type' => 'string']
     *         ],
     *         'required' => ['steps', 'final_answer']
     *     ],
     *     name: 'math_reasoning',
     *     strict: true
     * );
     */
    public static function jsonSchema(
        array $schema,
        ?string $name = null,
        ?bool $strict = true
    ): self
    {
        if (empty($schema)) {
            throw new InvalidArgumentException('Schema must not be empty');
        }

        return new self(
            type: self::TYPE_JSON_SCHEMA,
            schema: $schema,
            name: $name,
            strict: $strict
        );
    }

    /**
     * Создает формат с текстом.
     * @return self
     * @example
     * $format = AnswerFormat::text();
     */
    public static function text(): self
    {
        return new self(type: self::TYPE_TEXT);
    }

    /**
     * Преобразует формат в массив для передачи провайдеру.
     * 
     * Автоматически обрабатывает классы в схеме, заменяя их на JSON Schema
     * через вызов метода getJsonSchema() у классов, реализующих JsonSchemaInterface.
     *
     * @return array{type: string, name?: string, strict?: bool, schema?: array}
     */
    public function toArray(): array
    {
        $result = ['type' => $this->type];

        if ($this->type === self::TYPE_JSON_SCHEMA) {
            $result['name'] = $this->name;
            $result['strict'] = $this->strict;
            
            // Обрабатываем схему, заменяя классы на их JSON Schema
            // Создаем контекст корневой схемы для передачи в getJsonSchema()
            $rootSchema = [
                'name' => $this->name,
                'strict' => $this->strict,
            ];
            $result['schema'] = $this->schema !== null 
                ? $this->processSchema($this->schema, $rootSchema) 
                : null;
        }

        return $result;
    }

    /**
     * Рекурсивно обрабатывает схему, заменяя классы на их JSON Schema
     * 
     * Ищет во всех местах, где есть ключ 'type', и если значение - это класс,
     * реализующий JsonSchemaInterface, заменяет его на результат getJsonSchema().
     * 
     * @param mixed $value Значение для обработки (может быть массив, строка, и т.д.)
     * @param array|null $rootSchema Контекст корневой схемы (для передачи в getJsonSchema)
     * @return mixed Обработанное значение
     */
    private function processSchema(mixed $value, ?array $rootSchema = null): mixed
    {
        // Если это массив - рекурсивно обрабатываем
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                // Специальная обработка для ключа 'type'
                if ($key === 'type') {
                    $result[$key] = $this->processType($item, $rootSchema);
                } else {
                    // Рекурсивно обрабатываем остальные значения
                    $result[$key] = $this->processSchema($item, $rootSchema);
                }
            }
            return $result;
        }
        
        // Для не-массивов возвращаем как есть
        return $value;
    }

    /**
     * Обрабатывает значение поля 'type'
     * 
     * Если type - это класс, реализующий JsonSchemaInterface, заменяет его на JSON Schema.
     * Если type - массив, обрабатывает каждый элемент.
     * 
     * @param mixed $type Значение поля 'type' (может быть строка, класс, массив)
     * @param array|null $rootSchema Контекст корневой схемы (для передачи в getJsonSchema)
     * @return mixed Обработанное значение
     */
    private function processType(mixed $type, ?array $rootSchema = null): mixed
    {
        // Если это массив типов (например, ['string', 'null'] или [Class::class, 'string'])
        if (is_array($type)) {
            return array_map(
                fn($t) => $this->processType($t, $rootSchema),
                $type
            );
        }
        
        // Если это строка и это класс
        if (is_string($type) && class_exists($type)) {
            return $this->resolveClassToSchema($type, $rootSchema);
        }
        
        // Иначе возвращаем как есть (обычный тип: 'string', 'object', 'integer', etc.)
        return $type;
    }

    /**
     * Преобразует класс в JSON Schema
     * 
     * Вызывает статический метод getJsonSchema() у класса и рекурсивно
     * обрабатывает полученную схему (на случай вложенных классов).
     * 
     * @param string $className Имя класса
     * @param array|null $rootSchema Контекст корневой схемы (передается в getJsonSchema)
     * @return array JSON Schema массив
     * 
     * @throws InvalidArgumentException Если класс не существует или не реализует JsonSchemaInterface
     */
    private function resolveClassToSchema(string $className, ?array $rootSchema = null): array
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Class {$className} does not exist");
        }
        
        if (!is_subclass_of($className, JsonSchemaInterface::class)) {
            throw new InvalidArgumentException(
                "Class {$className} must implement " . JsonSchemaInterface::class
            );
        }
        
        // Создаем контекст для передачи в getJsonSchema
        // Включаем информацию о текущей схеме (name, strict и т.д.)
        $context = $rootSchema ?? [];
        if ($this->type === self::TYPE_JSON_SCHEMA) {
            $context['name'] = $this->name;
            $context['strict'] = $this->strict;
        }
        
        // Вызываем статический метод getJsonSchema() с контекстом
        $schema = $className::getJsonSchema($context);
        
        // Рекурсивно обрабатываем полученную схему (на случай вложенных классов)
        // Передаем контекст дальше
        return $this->processSchema($schema, $context);
    }

    /**
     * Преобразует JSON Schema в строку JSON.
     *
     * Доступен только для формата TYPE_JSON_SCHEMA.
     *
     * @param int $flags Флаги для json_encode (по умолчанию: PRETTY_PRINT | UNESCAPED_UNICODE | UNESCAPED_SLASHES)
     *
     * @return string JSON-строка схемы
     *
     * @throws \LogicException Если тип не TYPE_JSON_SCHEMA или schema === null
     * @throws \RuntimeException Если json_encode вернул false
     *
     * @example
     * $format = ResponseFormat::jsonSchema(['type' => 'object', 'properties' => [...]]);
     * $jsonString = $format->schemaToJsonString();
     * // Использование в system prompt
     * Message::system("Return only JSON matching schema: {$jsonString}");
     */
    public function schemaToJsonString(
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        if ($this->type !== self::TYPE_JSON_SCHEMA) {
            throw new \LogicException('schemaToJsonString() доступен только для TYPE_JSON_SCHEMA');
        }

        if ($this->schema === null) {
            throw new \LogicException('schemaToJsonString() вызван, но schema === null');
        }

        // Обрабатываем схему перед преобразованием в JSON (заменяем классы на их JSON Schema)
        // Создаем контекст корневой схемы для передачи в getJsonSchema()
        $rootSchema = [
            'name' => $this->name,
            'strict' => $this->strict,
        ];
        $processedSchema = $this->processSchema($this->schema, $rootSchema);

        $json = json_encode($processedSchema, $flags);

        if ($json === false) {
            throw new \RuntimeException('Ошибка json_encode для schema: ' . json_last_error_msg());
        }

        return $json;
    }

}