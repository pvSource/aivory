<?php

namespace PvSource\Aivory\Providers\OpenAI;

use PvSource\Aivory\LLM\ProviderInterface;
use PvSource\Aivory\LLM\Turn\Request\Request;
use PvSource\Aivory\LLM\Turn\Response\ResponseInterface;
use PvSource\Aivory\Providers\OpenAI\Turn\RequestAdapter;
use PvSource\Aivory\Transport\HttpClientInterface;

/**
 * Провайдер для работы с OpenAI API (ChatGPT)
 * 
 * Реализует ProviderInterface для взаимодействия с OpenAI AI API.
 * Отвечает за преобразование доменных моделей в формат OpenAI API
 * и обработку ответов от API.
 * 
 * Особенности OpenAI API:
 * - Endpoint: https://api.openai.com/v1/chat/completions
 * - JSON Schema передается через response_format.json_schema (отдельное поле)
 * - Не поддерживает режим "мышления" (thinking mode) - это специфика DeepSeek
 * - system_fingerprint может отсутствовать в некоторых версиях API (опционально)
 * - Не поддерживает reasoning_content в ответах
 * 
 * Основные отличия от DeepSeek:
 * - JSON Schema: OpenAI использует отдельное поле json_schema, DeepSeek - системное сообщение
 * - Thinking mode: OpenAI не поддерживает, DeepSeek поддерживает
 * - system_fingerprint: в OpenAI опционально, в DeepSeek всегда присутствует
 * 
 * Процесс работы:
 * 1. Преобразует доменный Request в PSR-7 Request через RequestAdapter
 * 2. Отправляет HTTP запрос через HttpClientInterface
 * 3. Валидирует HTTP статус-код
 * 4. Парсит JSON ответ
 * 5. Валидирует структуру ответа
 * 6. Извлекает HTTP заголовки
 * 7. Извлекает ResponseFormat из Request (для getSchemaObjects())
 * 8. Создает и возвращает ChatTurnResponse DTO
 * 
 * @package PvSource\Aivory\Providers\OpenAI
 * 
 * @see \PvSource\Aivory\LLM\ProviderInterface Интерфейс, который реализует класс
 * @see \PvSource\Aivory\Providers\OpenAI\Turn\RequestAdapter Используется для преобразования запросов
 * @see \PvSource\Aivory\Providers\OpenAI\ChatTurnResponse Создаваемый тип ответа
 * @see \PvSource\Aivory\Providers\ProviderFactory Создает экземпляры через фабрику
 * 
 * @example
 * // Создание через ProviderFactory (рекомендуемый способ)
 * $provider = ProviderFactory::create(
 *     'openai',
 *     $httpClient,
 *     ['apiKey' => 'sk-...']
 * );
 * 
 * // Отправка запроса
 * $request = (new RequestBuilder())
 *     ->setUserPrompt('Привет')
 *     ->setModel('gpt-4o')
 *     ->build();
 * 
 * $response = $provider->sendChatTurn($request);
 * 
 * @example
 * // С JSON Schema (OpenAI использует отдельное поле)
 * $request = (new RequestBuilder())
 *     ->setUserPrompt('Реши задачу')
 *     ->setModel('gpt-4o')
 *     ->setResponseFormat(ResponseFormat::jsonSchema([
 *         'type' => MathSolution::class
 *     ], name: 'math_solution'))
 *     ->build();
 * 
 * $response = $provider->sendChatTurn($request);
 * $mathSolution = $response->getSchemaObjects();
 */
class OpenAIProvider implements ProviderInterface
{
    /**
     * Базовый URI для OpenAI API
     * 
     * @var string
     */
    private const BASE_URI = 'https://api.openai.com';

    /**
     * Создает новый OpenAIProvider
     * 
     * @param HttpClientInterface $httpClient HTTP клиент для отправки запросов
     * @param RequestAdapter $requestAdapter Адаптер для преобразования доменных запросов в PSR-7 Request
     * @param string $apiKey API ключ для аутентификации в OpenAI API
     * @param string $baseUri Базовый URI API (по умолчанию 'https://api.openai.com')
     * 
     * @example
     * // Создание с настройками по умолчанию
     * $provider = new OpenAIProvider(
     *     httpClient: $httpClient,
     *     requestAdapter: $requestAdapter,
     *     apiKey: 'sk-...'
     * );
     * 
     * @example
     * // С кастомным baseUri (для тестирования или прокси)
     * $provider = new OpenAIProvider(
     *     httpClient: $httpClient,
     *     requestAdapter: $requestAdapter,
     *     apiKey: 'sk-...',
     *     baseUri: 'https://custom-proxy.com'
     * );
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private RequestAdapter $requestAdapter,
        private string $apiKey,
        private string $baseUri = self::BASE_URI,
    ) {}

    /**
     * Отправляет запрос к OpenAI API и возвращает ответ
     * 
     * Выполняет полный цикл взаимодействия с API:
     * 1. Преобразует доменный Request в PSR-7 Request через RequestAdapter
     * 2. Отправляет HTTP запрос через HttpClientInterface
     * 3. Проверяет HTTP статус-код (должен быть 2xx)
     * 4. Парсит JSON ответ от API
     * 5. Валидирует структуру ответа (проверяет наличие поля 'choices')
     * 6. Извлекает HTTP заголовки из ответа
     * 7. Извлекает ResponseFormat из Request (для поддержки getSchemaObjects())
     * 8. Создает и возвращает ChatTurnResponse DTO
     * 
     * Обработка ошибок:
     * - HTTP ошибки (4xx, 5xx) выбрасывают RuntimeException с деталями
     * - Ошибки парсинга JSON выбрасывают RuntimeException
     * - Некорректная структура ответа выбрасывает RuntimeException
     * 
     * @param Request $chatTurnRequest Доменный запрос с сообщениями и настройками
     * 
     * @return ResponseInterface Доменный ответ от OpenAI API (ChatTurnResponse)
     * 
     * @throws \RuntimeException Если произошла ошибка при взаимодействии с API:
     *                          - HTTP ошибка (неверный статус-код)
     *                          - Ошибка парсинга JSON ответа
     *                          - Некорректная структура ответа API
     *                          - Проблемы с сетью или таймауты
     * 
     * @example
     * // Базовое использование
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Что такое PHP?')
     *     ->setModel('gpt-4o')
     *     ->build();
     * 
     * $response = $provider->sendChatTurn($request);
     * $assistantMessage = $response->getAssistant();
     * echo $assistantMessage->content;
     * 
     * @example
     * // С обработкой ошибок
     * try {
     *     $response = $provider->sendChatTurn($request);
     *     // Обработка успешного ответа
     * } catch (\RuntimeException $e) {
     *     // Обработка ошибок API
     *     echo "Ошибка: " . $e->getMessage();
     * }
     * 
     * @example
     * // С использованием ResponseFormat и getSchemaObjects()
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Реши задачу')
     *     ->setModel('gpt-4o')
     *     ->setResponseFormat(ResponseFormat::jsonSchema([
     *         'type' => MathSolution::class
     *     ], name: 'math_solution', strict: true))
     *     ->build();
     * 
     * $response = $provider->sendChatTurn($request);
     * $mathSolution = $response->getSchemaObjects(); // Автоматическое преобразование в объект
     * echo $mathSolution->answer;
     */
    public function sendChatTurn(
        Request $chatTurnRequest
    ): ResponseInterface
    {
        // 1. Преобразуем доменный запрос в PSR-7 Request через адаптер
        $psrRequest = $this->requestAdapter->toPsrRequest(
            $chatTurnRequest,
            $this->baseUri,
            $this->apiKey
        );

        // 2. Отправляем запрос через HttpClientInterface
        $response = $this->httpClient->sendRequest($psrRequest);

        // 3. Проверяем HTTP статус-код
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $responseBody = $response->getBody()->getContents();
            throw new \RuntimeException(
                "OpenAI API returned error status {$statusCode}: {$responseBody}"
            );
        }

        // 4. Парсим ответ
        $responseBodyContent = $response->getBody()->getContents();
        $responseBody = json_decode($responseBodyContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Failed to parse JSON response from OpenAI API: ' . json_last_error_msg()
            );
        }

        if (!is_array($responseBody)) {
            throw new \RuntimeException('Invalid response from OpenAI API: expected array, got ' . gettype($responseBody));
        }

        // 5. Валидация структуры ответа
        if (!isset($responseBody['choices']) || !is_array($responseBody['choices'])) {
            throw new \RuntimeException('Invalid response structure from OpenAI API: missing or invalid "choices" field');
        }

        // 6. Извлекаем заголовки
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = $values;
        }

        // 7. Получаем ResponseFormat из Request (для getSchemaObjects())
        $responseFormat = $chatTurnRequest->getOptions()->responseFormat ?? null;

        // 8. Создаем ChatTurnResponse DTO
        return ChatTurnResponse::byResponse($responseBody, $headers, $responseFormat);
    }
}

