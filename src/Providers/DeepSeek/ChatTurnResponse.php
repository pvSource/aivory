<?php

namespace PvSource\Aivory\Providers\DeepSeek;

use PvSource\Aivory\LLM\Turn\Message\Message;
use PvSource\Aivory\LLM\Turn\Message\MessageCollection;
use PvSource\Aivory\LLM\Turn\Request\JsonSchemaInterface;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;
use PvSource\Aivory\LLM\Turn\Response\ResponseInterface;

/**
 * Response от DeepSeek API
 * 
 * Реализует ResponseInterface для работы с ответами от DeepSeek API.
 * Представляет DTO (Data Transfer Object) для ответа от DeepSeek API.
 * 
 * Особенности:
 * - Извлекает сообщения из поля `choices`
 * - Поддерживает `reasoning_content` (когда включен режим thinking)
 * - Реализует lazy loading для schema-bound объектов через getSchemaObjects()
 * - Сохраняет сырые данные ответа и HTTP заголовки
 * 
 * Структура ответа DeepSeek API:
 * - id: уникальный идентификатор ответа
 * - choices: массив вариантов ответов (обычно один)
 * - created: timestamp создания ответа
 * - model: название модели, которая сгенерировала ответ
 * - system_fingerprint: отпечаток системы (всегда присутствует в DeepSeek)
 * - object: тип объекта (обычно 'chat.completion')
 * - usage: информация об использовании токенов
 * 
 * @package PvSource\Aivory\Providers\DeepSeek
 * 
 * @see \PvSource\Aivory\LLM\Turn\Response\ResponseInterface Интерфейс, который реализует класс
 * @see \PvSource\Aivory\Providers\DeepSeek\DeepSeekProvider Создает экземпляры этого класса
 */
class ChatTurnResponse implements ResponseInterface
{
    /**
     * Коллекция сообщений из ответа
     * 
     * Извлекается из поля `choices` при создании объекта.
     * Содержит сообщения от ассистента (и другие, если есть).
     * 
     * @var MessageCollection
     */
    private MessageCollection $messages;

    /**
     * Сырое тело ответа от API
     * 
     * Полный массив ответа от DeepSeek API без изменений.
     * Используется для доступа к дополнительным полям, не представленным в классе.
     * 
     * @var array
     */
    private array $rawBody;

    /**
     * HTTP заголовки ответа
     * 
     * Массив HTTP заголовков от API ответа.
     * Формат: ['header-name' => ['value1', 'value2']]
     * 
     * @var array<string, string[]>
     */
    private array $headers;

    /**
     * Формат ответа (схема) для getSchemaObjects()
     * 
     * Используется для преобразования JSON ответа в типизированные объекты.
     * Передается из Request при создании ответа.
     * 
     * @var ResponseFormat|null
     */
    private ?ResponseFormat $responseFormat;

    /**
     * Lazy loaded объекты, соответствующие JSON Schema
     * 
     * Создаются при первом вызове getSchemaObjects() и кэшируются для последующих вызовов.
     * 
     * @var object|null
     */
    private ?object $schemaObjects = null;

    /**
     * Создает новый ChatTurnResponse
     * 
     * @param string $id Уникальный идентификатор ответа от DeepSeek API
     * @param array $choices Массив вариантов ответов (обычно содержит один элемент)
     * @param int $created Unix timestamp создания ответа
     * @param string $model Название модели, которая сгенерировала ответ
     * @param string $systemFingerprint Отпечаток системы (всегда присутствует в DeepSeek)
     * @param string $object Тип объекта (обычно 'chat.completion')
     * @param array $usage Информация об использовании токенов ['prompt_tokens' => int, 'completion_tokens' => int, 'total_tokens' => int]
     * @param array $rawBody Сырое тело ответа от API (по умолчанию пустой массив)
     * @param array $headers HTTP заголовки ответа (по умолчанию пустой массив)
     * @param ResponseFormat|null $responseFormat Формат ответа для getSchemaObjects() (по умолчанию null)
     * 
     * @example
     * // Создание через byResponse() (рекомендуемый способ)
     * $response = ChatTurnResponse::byResponse($apiResponse, $headers, $responseFormat);
     * 
     * @example
     * // Прямое создание (редко используется)
     * $response = new ChatTurnResponse(
     *     id: 'chatcmpl-123',
     *     choices: [['message' => [...]]],
     *     created: 1234567890,
     *     model: 'deepseek-chat',
     *     systemFingerprint: 'fp_xxx',
     *     object: 'chat.completion',
     *     usage: ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
     *     rawBody: $apiResponse,
     *     headers: $headers,
     *     responseFormat: $responseFormat
     * );
     */
    public function __construct(
        public string $id,
        public array  $choices,
        public int    $created,
        public string $model,
        public string $systemFingerprint,
        public string $object,
        public array  $usage,
        array $rawBody = [],
        array $headers = [],
        ?ResponseFormat $responseFormat = null
    ) {
        $this->rawBody = $rawBody;
        $this->headers = $headers;
        $this->responseFormat = $responseFormat;
        
        // Извлекаем сообщения из choices
        $messages = [];
        foreach ($this->choices as $choice) {
            if (isset($choice['message']['role']) && isset($choice['message']['content'])) {
                // Извлекаем reasoning_content, если он есть (когда включен isThinking)
                $reasoningContent = $choice['message']['reasoning_content'] ?? null;
                
                $messages[] = new Message(
                    role: $choice['message']['role'],
                    content: $choice['message']['content'],
                    reasoningContent: $reasoningContent
                );
            }
        }
        $this->messages = new MessageCollection($messages);
    }

    /**
     * Создает ChatTurnResponse из массива ответа API
     * 
     * Фабричный метод для создания объекта из сырого ответа DeepSeek API.
     * Выполняет валидацию обязательных полей и создает объект.
     * 
     * Обязательные поля в ответе:
     * - id: уникальный идентификатор
     * - choices: массив вариантов ответов
     * - created: timestamp создания
     * - model: название модели
     * - system_fingerprint: отпечаток системы
     * - object: тип объекта
     * - usage: информация об использовании токенов
     * 
     * @param array $response Массив ответа от DeepSeek API (результат json_decode)
     * @param array $headers HTTP заголовки ответа (по умолчанию пустой массив)
     * @param ResponseFormat|null $responseFormat Схема ответа для getSchemaObjects() (по умолчанию null)
     * 
     * @return self Экземпляр ChatTurnResponse
     * 
     * @throws \RuntimeException Если отсутствует обязательное поле в ответе API
     * 
     * @example
     * // Использование в провайдере
     * $responseBody = json_decode($httpResponse->getBody()->getContents(), true);
     * $headers = $httpResponse->getHeaders();
     * $responseFormat = $request->getOptions()->responseFormat;
     * 
     * $chatResponse = ChatTurnResponse::byResponse($responseBody, $headers, $responseFormat);
     */
    public static function byResponse(array $response, array $headers = [], ?ResponseFormat $responseFormat = null): self
    {
        // Валидация обязательных полей
        $requiredFields = ['id', 'choices', 'created', 'model', 'system_fingerprint', 'object', 'usage'];
        foreach ($requiredFields as $field) {
            if (!isset($response[$field])) {
                throw new \RuntimeException("Missing required field in DeepSeek API response: $field");
            }
        }

        return new self(
            id: $response['id'],
            choices: $response['choices'],
            created: $response['created'],
            model: $response['model'],
            systemFingerprint: $response['system_fingerprint'],
            object: $response['object'],
            usage: $response['usage'],
            rawBody: $response,
            headers: $headers,
            responseFormat: $responseFormat
        );
    }

    /**
     * Возвращает коллекцию сообщений из ответа
     * 
     * Сообщения извлекаются из поля `choices` при создании объекта.
     * Обычно содержит одно сообщение от ассистента.
     * 
     * @return MessageCollection Коллекция сообщений
     * 
     * @example
     * $messages = $response->getMessages();
     * foreach ($messages as $message) {
     *     echo "[{$message->role}]: {$message->content}\n";
     * }
     */
    public function getMessages(): MessageCollection
    {
        return $this->messages;
    }

    /**
     * Возвращает сырое тело ответа от API
     * 
     * Полный массив ответа от DeepSeek API без изменений.
     * Полезно для доступа к дополнительным полям, не представленным в классе.
     * 
     * @return array Сырое тело ответа (массив)
     * 
     * @example
     * $rawBody = $response->getRawBody();
     * if (isset($rawBody['some_custom_field'])) {
     *     // Доступ к полю, не представленному в классе
     * }
     */
    public function getRawBody(): array
    {
        return $this->rawBody;
    }

    /**
     * Возвращает HTTP заголовки ответа
     * 
     * @return array<string, string[]> Массив HTTP заголовков
     * 
     * @example
     * $headers = $response->getHeaders();
     * if (isset($headers['X-RateLimit-Remaining'])) {
     *     echo "Осталось запросов: " . $headers['X-RateLimit-Remaining'][0];
     * }
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Возвращает содержимое первого сообщения (для обратной совместимости)
     * 
     * Удобный метод для быстрого доступа к тексту ответа ассистента.
     * Если сообщений нет, возвращает пустую строку.
     * 
     * @return string Содержимое первого сообщения или пустая строка
     * 
     * @example
     * // Быстрый доступ к ответу
     * $content = $response->getContent();
     * echo $content;
     * 
     * @deprecated Рекомендуется использовать getAssistant()->content для более явного доступа
     */
    public function getContent(): string
    {
        if ($this->messages->isEmpty()) {
            return '';
        }
        
        $firstMessage = $this->messages->first();
        return $firstMessage?->content ?? '';
    }

    /**
     * Возвращает уникальный идентификатор ответа
     * 
     * @return string ID ответа (например, 'chatcmpl-1234567890')
     * 
     * @example
     * $id = $response->getId();
     * echo "ID ответа: {$id}";
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Возвращает информацию об использовании токенов
     * 
     * Содержит:
     * - prompt_tokens: количество токенов в запросе
     * - completion_tokens: количество токенов в ответе
     * - total_tokens: общее количество токенов
     * 
     * @return array Массив с информацией об использовании токенов
     * 
     * @example
     * $usage = $response->getUsage();
     * echo "Потрачено токенов: {$usage['total_tokens']}";
     * echo "Запрос: {$usage['prompt_tokens']}, Ответ: {$usage['completion_tokens']}";
     */
    public function getUsage(): array
    {
        return $this->usage;
    }

    /**
     * Возвращает Unix timestamp создания ответа
     * 
     * @return int Unix timestamp (количество секунд с 1 января 1970 года)
     * 
     * @example
     * $timestamp = $response->getTimestamp();
     * $date = date('Y-m-d H:i:s', $timestamp);
     * echo "Ответ создан: {$date}";
     */
    public function getTimestamp(): int
    {
        return $this->created;
    }

    /**
     * Возвращает название модели, которая сгенерировала ответ
     * 
     * @return string Название модели (например, 'deepseek-chat')
     * 
     * @example
     * $model = $response->getModelName();
     * echo "Использована модель: {$model}";
     */
    public function getModelName(): string
    {
        return $this->model;
    }

    /**
     * Возвращает первое сообщение от ассистента
     * 
     * Ищет в коллекции сообщений первое сообщение с ролью 'assistant'
     * и возвращает его.
     * 
     * @return Message Сообщение от ассистента
     * 
     * @throws \RuntimeException Если сообщение от ассистента не найдено в ответе
     * 
     * @example
     * $assistantMessage = $response->getAssistant();
     * echo "Ответ ассистента: {$assistantMessage->content}";
     * 
     * // Доступ к reasoning_content (если включен isThinking)
     * if ($assistantMessage->reasoningContent !== null) {
     *     echo "Размышления: {$assistantMessage->reasoningContent}";
     * }
     */
    public function getAssistant(): Message
    {
        foreach ($this->messages as $message) {
            if ($message->role === Message::ROLE_ASSISTANT) {
                return $message;
            }
        }

        throw new \RuntimeException("Сообщение от ассистента не найдено в ответе");
    }

    /**
     * Возвращает объекты, соответствующие JSON Schema, из ответа ассистента
     * 
     * Преобразует JSON ответ ассистента в типизированные объекты согласно схеме.
     * Использует lazy loading - объекты создаются при первом вызове и кэшируются.
     * 
     * Процесс работы:
     * 1. Проверяет наличие ResponseFormat
     * 2. Проверяет, что тип ResponseFormat - TYPE_JSON_SCHEMA
     * 3. Парсит JSON из содержимого сообщения ассистента
     * 4. Находит корневой класс в схеме (должен реализовывать JsonSchemaInterface)
     * 5. Создает объект через JsonSchemaInterface::fromResponse()
     * 6. Кэширует объект для последующих вызовов
     * 
     * @param ResponseFormat|null $responseFormat Опционально, можно передать конкретный ResponseFormat,
     *                                            если он отличается от того, что был в запросе.
     *                                            Если null - используется ResponseFormat из запроса
     * 
     * @return object Корневой объект, соответствующий JSON Schema
     * 
     * @throws \LogicException Если ResponseFormat не установлен или не является TYPE_JSON_SCHEMA
     * @throws \RuntimeException Если JSON из ответа ассистента невалиден или не соответствует схеме
     * @throws \LogicException Если не удалось найти корневой класс в схеме
     * @throws \LogicException Если класс не реализует JsonSchemaInterface
     * 
     * @example
     * // Базовое использование
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Реши задачу')
     *     ->setResponseFormat(ResponseFormat::jsonSchema([
     *         'type' => MathSolution::class
     *     ]))
     *     ->build();
     * 
     * $response = $provider->sendChatTurn($request);
     * $mathSolution = $response->getSchemaObjects(); // Автоматическое преобразование
     * echo $mathSolution->answer;
     * 
     * @example
     * // С переопределением ResponseFormat
     * $response = $provider->sendChatTurn($request);
     * $alternativeFormat = ResponseFormat::jsonSchema([...]);
     * $objects = $response->getSchemaObjects($alternativeFormat);
     */
    public function getSchemaObjects(?ResponseFormat $responseFormat = null): object
    {
        // Lazy loading: если объекты уже созданы, возвращаем их
        if ($this->schemaObjects !== null) {
            return $this->schemaObjects;
        }

        // Используем переданный ResponseFormat или сохраненный
        $format = $responseFormat ?? $this->responseFormat;

        if ($format === null) {
            throw new \LogicException(
                "ResponseFormat не установлен. Установите ResponseFormat в Request или передайте его в метод getSchemaObjects()."
            );
        }

        if ($format->type !== ResponseFormat::TYPE_JSON_SCHEMA) {
            throw new \LogicException(
                "getSchemaObjects() доступен только для ResponseFormat с типом TYPE_JSON_SCHEMA. " .
                "Текущий тип: {$format->type}"
            );
        }

        // Получаем сообщение от ассистента
        $assistantMessage = $this->getAssistant();

        // Парсим JSON из содержимого сообщения
        $responseData = json_decode($assistantMessage->content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "Ошибка парсинга JSON из ответа ассистента: " . json_last_error_msg()
            );
        }

        if (!is_array($responseData)) {
            throw new \RuntimeException(
                "Ответ ассистента должен быть JSON объектом (массивом), получен: " . gettype($responseData)
            );
        }

        // Получаем схему и находим корневой класс
        $schema = $format->schema;
        if ($schema === null) {
            throw new \LogicException("Схема ResponseFormat пуста");
        }

        // Находим корневой класс в схеме
        $rootClass = $this->findRootClassInSchema($schema);

        if ($rootClass === null) {
            throw new \LogicException(
                "Не удалось найти класс, реализующий JsonSchemaInterface, в корне схемы. " .
                "Убедитесь, что в схеме указан класс в поле 'type', например: ['type' => MathSolution::class]"
            );
        }

        // Проверяем, что класс реализует JsonSchemaInterface
        if (!is_subclass_of($rootClass, JsonSchemaInterface::class)) {
            throw new \LogicException(
                "Класс {$rootClass} не реализует " . JsonSchemaInterface::class
            );
        }

        // Создаем объект через fromResponse() и сохраняем для lazy loading
        $this->schemaObjects = $rootClass::fromResponse($responseData);

        return $this->schemaObjects;
    }

    /**
     * Находит корневой класс, реализующий JsonSchemaInterface, в схеме
     * 
     * Ищет в схеме поле 'type', которое содержит имя класса.
     * Класс должен существовать и реализовывать JsonSchemaInterface.
     * 
     * Поддерживаемые форматы схемы:
     * - ['type' => MathSolution::class] - класс напрямую в поле type
     * 
     * Не поддерживается:
     * - ['type' => 'object', 'properties' => [...]] - обычная JSON Schema без класса
     * 
     * @param array $schema JSON Schema массив
     * @return string|null Имя класса (FQCN) или null, если не найден
     * 
     * @example
     * // Схема с классом
     * $schema = ['type' => MathSolution::class];
     * $class = $this->findRootClassInSchema($schema); // MathSolution::class
     * 
     * @example
     * // Схема без класса
     * $schema = ['type' => 'object', 'properties' => [...]];
     * $class = $this->findRootClassInSchema($schema); // null
     */
    private function findRootClassInSchema(array $schema): ?string
    {
        // Если в схеме есть 'type' и это класс
        if (isset($schema['type'])) {
            $type = $schema['type'];
            
            // Если type - это строка и это существующий класс
            if (is_string($type) && class_exists($type)) {
                // Проверяем, что класс реализует JsonSchemaInterface
                if (is_subclass_of($type, JsonSchemaInterface::class)) {
                    return $type;
                }
            }
        }

        return null;
    }
}
