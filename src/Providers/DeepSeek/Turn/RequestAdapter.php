<?php

namespace PvSource\Aivory\Providers\DeepSeek\Turn;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use PvSource\Aivory\LLM\Turn\Message\Message;
use PvSource\Aivory\LLM\Turn\Request\Request;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;

/**
 * Adapter для преобразования доменного Request в PSR-7 Request для DeepSeek API
 * 
 * Реализует паттерн Adapter, преобразуя доменные модели (Request, TurnOptions)
 * в формат, специфичный для DeepSeek API.
 * 
 * Принципы работы:
 * - Инкапсулирует всю специфику DeepSeek API
 * - Преобразует доменные модели в формат API
 * - Создает PSR-7 совместимые HTTP запросы
 * - Обрабатывает особенности DeepSeek (thinking, response_format)
 * 
 * Специфика DeepSeek API:
 * - Endpoint: /chat/completions
 * - Thinking mode: поддерживается через поле `thinking: {type: 'enabled'}`
 * - Response Format: JSON Schema передается через системное сообщение (не через отдельное поле)
 * - Именование полей: snake_case (frequency_penalty, max_tokens и т.д.)
 * 
 * @package PvSource\Aivory\Providers\DeepSeek\Turn
 * 
 * @see \PvSource\Aivory\Providers\DeepSeek\DeepSeekProvider Используется для преобразования запросов
 */
class RequestAdapter
{
    /**
     * Endpoint для запросов к DeepSeek API
     * 
     * @var string
     */
    private const ENDPOINT = '/chat/completions';
    
    /**
     * Создает новый RequestAdapter
     * 
     * @param RequestFactoryInterface $requestFactory Фабрика для создания PSR-7 Request
     * @param StreamFactoryInterface $streamFactory Фабрика для создания потоков (тело запроса)
     * @param UriFactoryInterface $uriFactory Фабрика для создания URI
     */
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
    ) {}
    
    /**
     * Преобразует доменный запрос в массив payload для DeepSeek API
     * 
     * Выполняет следующие преобразования:
     * 1. Преобразует MessageCollection в массив сообщений
     * 2. Извлекает модель из TurnOptions
     * 3. Преобразует параметры генерации (camelCase -> snake_case)
     * 4. Обрабатывает специфичные для DeepSeek параметры (thinking)
     * 5. Обрабатывает ResponseFormat:
     *    - TYPE_JSON_SCHEMA: добавляет системное сообщение со схемой
     *    - TYPE_TEXT: устанавливает response_format.type = 'text'
     * 
     * Особенности:
     * - Включает только не-null значения (оптимизация payload)
     * - Для JSON Schema добавляет системное сообщение вместо отдельного поля json_schema
     * - Преобразует имена полей из camelCase в snake_case
     * 
     * @param Request $turnRequest Доменный запрос с сообщениями и настройками
     * @return array Массив с данными для JSON payload, готовый для отправки в DeepSeek API
     * 
     * @example
     * // Базовый запрос
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Привет')
     *     ->setModel('deepseek-chat')
     *     ->build();
     * 
     * $payload = $adapter->toPayload($request);
     * // Результат: ['messages' => [...], 'model' => 'deepseek-chat']
     * 
     * @example
     * // С thinking mode
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Реши задачу')
     *     ->setModel('deepseek-chat')
     *     ->setIsThinking(true)
     *     ->build();
     * 
     * $payload = $adapter->toPayload($request);
     * // Результат: ['messages' => [...], 'model' => 'deepseek-chat', 'thinking' => ['type' => 'enabled']]
     * 
     * @example
     * // С JSON Schema (добавляется системное сообщение)
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Реши задачу')
     *     ->setModel('deepseek-chat')
     *     ->setResponseFormat(ResponseFormat::jsonSchema([...]))
     *     ->build();
     * 
     * $payload = $adapter->toPayload($request);
     * // Результат: ['messages' => [..., системное сообщение со схемой], 'model' => 'deepseek-chat', 'response_format' => ['type' => 'json_object']]
     */
    public function toPayload(Request $turnRequest): array
    {
        $requestBody = [];
        
        // Messages
        $requestBody['messages'] = $turnRequest->getMessages()->toArray();
        
        // Options
        $options = $turnRequest->getOptions();
        $requestBody['model'] = $options->model;
        
        // DeepSeek специфика: thinking
        if ($options->isThinking !== null && $options->isThinking) {
            $requestBody['thinking'] = ['type' => 'enabled'];
        }
        
        // Добавляем только не-null значения
        if ($options->frequencyPenalty !== null) {
            $requestBody['frequency_penalty'] = $options->frequencyPenalty;
        }
        if ($options->maxTokens !== null) {
            $requestBody['max_tokens'] = $options->maxTokens;
        }
        if ($options->presencePenalty !== null) {
            $requestBody['presence_penalty'] = $options->presencePenalty;
        }
        if ($options->stop !== null) {
            $requestBody['stop'] = $options->stop;
        }
        if ($options->stream !== null) {
            $requestBody['stream'] = $options->stream;
        }
        if ($options->streamOptions !== null) {
            $requestBody['stream_options'] = $options->streamOptions;
        }
        if ($options->temperature !== null) {
            $requestBody['temperature'] = $options->temperature;
        }
        if ($options->topP !== null) {
            $requestBody['top_p'] = $options->topP;
        }
        if ($options->tools !== null) {
            $requestBody['tools'] = $options->tools;
        }
        if ($options->toolChoice !== null) {
            $requestBody['tool_choice'] = $options->toolChoice;
        }
        if ($options->logprobs !== null) {
            $requestBody['logprobs'] = $options->logprobs;
        }
        if ($options->topLogprobs !== null) {
            $requestBody['top_logprobs'] = $options->topLogprobs;
        }

        // Response Format (специфика DeepSeek)
        if ($options->responseFormat !== null) {
            if ($options->responseFormat->type === ResponseFormat::TYPE_JSON_SCHEMA) {
                $requestBody['response_format'] = ['type' => 'json_object'];
                // Добавляем системное сообщение со схемой
                $messagesArray = $requestBody['messages'];
                $messagesArray[] = Message::system(
                    'Return only JSON matching schema: ' . $options->responseFormat->schemaToJsonString()
                )->toArray();
                $requestBody['messages'] = $messagesArray;
            } elseif ($options->responseFormat->type === ResponseFormat::TYPE_TEXT) {
                $requestBody['response_format'] = ['type' => 'text'];
            }
        }

        return $requestBody;
    }
    
    /**
     * Преобразует доменный запрос в PSR-7 Request
     * 
     * Создает полностью готовый HTTP запрос для отправки в DeepSeek API:
     * 1. Преобразует доменный Request в payload через toPayload()
     * 2. Создает URI из baseUri и ENDPOINT
     * 3. Создает PSR-7 Request с методом POST
     * 4. Устанавливает необходимые заголовки:
     *    - Authorization: Bearer {apiKey}
     *    - Content-Type: application/json
     * 5. Устанавливает тело запроса (JSON encoded payload)
     * 
     * @param Request $turnRequest Доменный запрос с сообщениями и настройками
     * @param string $baseUri Базовый URI API (например, 'https://api.deepseek.com')
     * @param string $apiKey API ключ для аутентификации
     * 
     * @return PsrRequestInterface PSR-7 Request, готовый к отправке через HTTP клиент
     * 
     * @throws \JsonException Если не удалось закодировать payload в JSON
     * 
     * @example
     * // Создание запроса
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Привет')
     *     ->setModel('deepseek-chat')
     *     ->build();
     * 
     * $psrRequest = $adapter->toPsrRequest(
     *     $request,
     *     'https://api.deepseek.com',
     *     'sk-...'
     * );
     * 
     * // Отправка через HTTP клиент
     * $response = $httpClient->sendRequest($psrRequest);
     * 
     * @example
     * // Использование в провайдере
     * $psrRequest = $this->requestAdapter->toPsrRequest(
     *     $chatTurnRequest,
     *     $this->baseUri,
     *     $this->apiKey
     * );
     * $response = $this->httpClient->sendRequest($psrRequest);
     */
    public function toPsrRequest(
        Request $turnRequest,
        string $baseUri,
        string $apiKey
    ): PsrRequestInterface
    {
        // 1. Строим payload
        $payload = $this->toPayload($turnRequest);
        
        // 2. Создаем URI
        $uri = $this->uriFactory->createUri($baseUri . self::ENDPOINT);
        
        // 3. Создаем PSR-7 Request
        return $this->requestFactory->createRequest('POST', $uri)
            ->withHeader('Authorization', "Bearer {$apiKey}")
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $this->streamFactory->createStream(json_encode($payload))
            );
    }
}

