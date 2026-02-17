<?php

namespace PvSource\Aivory\Providers\DeepSeek;

use PvSource\Aivory\LLM\ProviderInterface;
use PvSource\Aivory\LLM\Turn\Request\Request;
use PvSource\Aivory\LLM\Turn\Response\ResponseInterface;
use PvSource\Aivory\Providers\DeepSeek\Turn\RequestAdapter;
use PvSource\Aivory\Transport\HttpClientInterface;

/**
 * Провайдер для работы с DeepSeek API
 * 
 * Реализует ProviderInterface для взаимодействия с DeepSeek AI API.
 * Отвечает за преобразование доменных моделей в формат DeepSeek API
 * и обработку ответов от API.
 * 
 * Особенности DeepSeek API:
 * - Endpoint: https://api.deepseek.com/chat/completions
 * - Поддерживает режим "мышления" (thinking mode) через параметр isThinking
 * - JSON Schema передается через системное сообщение (не через отдельное поле)
 * - Всегда возвращает system_fingerprint в ответе
 * - Поддерживает reasoning_content в ответах (когда включен thinking mode)
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
 * @package PvSource\Aivory\Providers\DeepSeek
 * 
 * @see \PvSource\Aivory\LLM\ProviderInterface Интерфейс, который реализует класс
 * @see \PvSource\Aivory\Providers\DeepSeek\Turn\RequestAdapter Используется для преобразования запросов
 * @see \PvSource\Aivory\Providers\DeepSeek\ChatTurnResponse Создаваемый тип ответа
 * @see \PvSource\Aivory\Providers\ProviderFactory Создает экземпляры через фабрику
 * 
 * @example
 * // Создание через ProviderFactory (рекомендуемый способ)
 * $provider = ProviderFactory::create(
 *     'deepseek',
 *     $httpClient,
 *     ['apiKey' => 'sk-...']
 * );
 * 
 * // Отправка запроса
 * $request = (new RequestBuilder())
 *     ->setUserPrompt('Привет')
 *     ->setModel('deepseek-chat')
 *     ->build();
 * 
 * $response = $provider->sendChatTurn($request);
 * 
 * @example
 * // С thinking mode
 * $request = (new RequestBuilder())
 *     ->setUserPrompt('Реши задачу')
 *     ->setModel('deepseek-chat')
 *     ->setIsThinking(true)
 *     ->build();
 * 
 * $response = $provider->sendChatTurn($request);
 * $assistantMessage = $response->getAssistant();
 * if ($assistantMessage->reasoningContent !== null) {
 *     echo "Размышления: {$assistantMessage->reasoningContent}\n";
 * }
 * echo "Ответ: {$assistantMessage->content}";
 */
class DeepSeekProvider implements ProviderInterface
{
    /**
     * Базовый URI для DeepSeek API
     * 
     * @var string
     */
    private const BASE_URI = 'https://api.deepseek.com';

    /**
     * Создает новый DeepSeekProvider
     * 
     * @param HttpClientInterface $httpClient HTTP клиент для отправки запросов
     * @param RequestAdapter $requestAdapter Адаптер для преобразования доменных запросов в PSR-7 Request
     * @param string $apiKey API ключ для аутентификации в DeepSeek API
     * @param string $baseUri Базовый URI API (по умолчанию 'https://api.deepseek.com')
     * 
     * @example
     * // Создание с настройками по умолчанию
     * $provider = new DeepSeekProvider(
     *     httpClient: $httpClient,
     *     requestAdapter: $requestAdapter,
     *     apiKey: 'sk-...'
     * );
     * 
     * @example
     * // С кастомным baseUri (для тестирования или прокси)
     * $provider = new DeepSeekProvider(
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
     * Отправляет запрос к DeepSeek API и возвращает ответ
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
     * @return ResponseInterface Доменный ответ от DeepSeek API (ChatTurnResponse)
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
     *     ->setModel('deepseek-chat')
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
     *     ->setModel('deepseek-chat')
     *     ->setResponseFormat(ResponseFormat::jsonSchema([
     *         'type' => MathSolution::class
     *     ]))
     *     ->build();
     * 
     * $response = $provider->sendChatTurn($request);
     * $mathSolution = $response->getSchemaObjects(); // Автоматическое преобразование в объект
     * echo $mathSolution->answer;
     * 
     * @example
     * // С thinking mode (получение reasoning_content)
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Сложная задача')
     *     ->setModel('deepseek-chat')
     *     ->setIsThinking(true)
     *     ->build();
     * 
     * $response = $provider->sendChatTurn($request);
     * $assistantMessage = $response->getAssistant();
     * 
     * if ($assistantMessage->reasoningContent !== null) {
     *     echo "Размышления:\n{$assistantMessage->reasoningContent}\n\n";
     * }
     * echo "Ответ:\n{$assistantMessage->content}";
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
                "DeepSeek API returned error status {$statusCode}: {$responseBody}"
            );
        }

        // 4. Парсим ответ
        $responseBodyContent = $response->getBody()->getContents();
        $responseBody = json_decode($responseBodyContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Failed to parse JSON response from DeepSeek API: ' . json_last_error_msg()
            );
        }

        if (!is_array($responseBody)) {
            throw new \RuntimeException('Invalid response from DeepSeek API: expected array, got ' . gettype($responseBody));
        }

        // 5. Валидация структуры ответа
        if (!isset($responseBody['choices']) || !is_array($responseBody['choices'])) {
            throw new \RuntimeException('Invalid response structure from DeepSeek API: missing or invalid "choices" field');
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