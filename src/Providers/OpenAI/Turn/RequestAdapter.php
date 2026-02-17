<?php

namespace PvSource\Aivory\Providers\OpenAI\Turn;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use PvSource\Aivory\LLM\Turn\Request\Request;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;

/**
 * Adapter для преобразования доменного Request в PSR-7 Request для OpenAI API
 * 
 * Реализует паттерн Adapter, преобразуя доменные модели (Request, TurnOptions)
 * в формат, специфичный для OpenAI API.
 * 
 * Принципы работы:
 * - Инкапсулирует всю специфику OpenAI API
 * - Преобразует доменные модели в формат API
 * - Создает PSR-7 совместимые HTTP запросы
 * - Обрабатывает особенности OpenAI (response_format.json_schema)
 * 
 * Специфика OpenAI API:
 * - Endpoint: /v1/chat/completions
 * - JSON Schema: передается через response_format.json_schema (отдельное поле)
 * - Thinking mode: не поддерживается (это специфика DeepSeek)
 * - Именование полей: snake_case (frequency_penalty, max_tokens и т.д.)
 * 
 * Основное отличие от DeepSeek:
 * - JSON Schema: OpenAI использует отдельное поле json_schema в response_format,
 *                DeepSeek передает схему через системное сообщение
 * 
 * @package PvSource\Aivory\Providers\OpenAI\Turn
 * 
 * @see \PvSource\Aivory\Providers\OpenAI\OpenAIProvider Используется для преобразования запросов
 */
class RequestAdapter
{
    /**
     * Endpoint для запросов к OpenAI API
     * 
     * @var string
     */
    private const ENDPOINT = '/v1/chat/completions';
    
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
     * Преобразует доменный запрос в массив payload для OpenAI API
     * 
     * Выполняет следующие преобразования:
     * 1. Преобразует MessageCollection в массив сообщений
     * 2. Извлекает модель из TurnOptions
     * 3. Преобразует параметры генерации (camelCase -> snake_case)
     * 4. Игнорирует isThinking (OpenAI не поддерживает thinking mode)
     * 5. Обрабатывает ResponseFormat:
     *    - TYPE_JSON_SCHEMA: создает response_format.json_schema с полями name, schema, strict
     *    - TYPE_TEXT: устанавливает response_format.type = 'text'
     * 
     * Особенности:
     * - Включает только не-null значения (оптимизация payload)
     * - Для JSON Schema создает структуру response_format.json_schema (отдельное поле)
     * - Преобразует имена полей из camelCase в snake_case
     * - Игнорирует isThinking (специфика DeepSeek)
     * 
     * @param Request $turnRequest Доменный запрос с сообщениями и настройками
     * @return array Массив с данными для JSON payload, готовый для отправки в OpenAI API
     * 
     * @example
     * // Базовый запрос
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Привет')
     *     ->setModel('gpt-4o')
     *     ->build();
     * 
     * $payload = $adapter->toPayload($request);
     * // Результат: ['messages' => [...], 'model' => 'gpt-4o']
     * 
     * @example
     * // С JSON Schema (OpenAI использует отдельное поле)
     * $request = (new RequestBuilder())
     *     ->setUserPrompt('Реши задачу')
     *     ->setModel('gpt-4o')
     *     ->setResponseFormat(ResponseFormat::jsonSchema([
     *         'type' => MathSolution::class
     *     ], name: 'math_solution', strict: true))
     *     ->build();
     * 
     * $payload = $adapter->toPayload($request);
     * // Результат: [
     * //   'messages' => [...],
     * //   'model' => 'gpt-4o',
     * //   'response_format' => [
     * //     'type' => 'json_schema',
     * //     'json_schema' => [
     * //       'name' => 'math_solution',
     * //       'schema' => [...],
     * //       'strict' => true
     * //     ]
     * //   ]
     * // ]
     */
    public function toPayload(Request $turnRequest): array
    {
        $requestBody = [];
        
        // Messages
        $requestBody['messages'] = $turnRequest->getMessages()->toArray();
        
        // Options
        $options = $turnRequest->getOptions();
        $requestBody['model'] = $options->model;
        
        // OpenAI не поддерживает thinking режим (это специфика DeepSeek)
        // Поэтому isThinking игнорируется
        
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

        // Response Format (специфика OpenAI)
        // OpenAI передает схему через отдельное поле response_format.json_schema
        if ($options->responseFormat !== null) {
            if ($options->responseFormat->type === ResponseFormat::TYPE_JSON_SCHEMA) {
                // Получаем обработанную схему (с замененными классами на JSON Schema)
                $processedSchema = $options->responseFormat->toArray()['schema'];
                
                // OpenAI использует формат:
                // response_format: {
                //   type: "json_schema",
                //   json_schema: {
                //     name: "schema_name",
                //     schema: { ... },
                //     strict: true/false
                //   }
                // }
                $requestBody['response_format'] = [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => $options->responseFormat->name ?? 'response',
                        'schema' => $processedSchema,
                        'strict' => $options->responseFormat->strict ?? true,
                    ]
                ];
            } elseif ($options->responseFormat->type === ResponseFormat::TYPE_TEXT) {
                // Для текстового формата OpenAI использует просто type: "text"
                $requestBody['response_format'] = ['type' => 'text'];
            }
        }

        return $requestBody;
    }
    
    /**
     * Преобразует доменный запрос в PSR-7 Request
     * 
     * Создает полностью готовый HTTP запрос для отправки в OpenAI API:
     * 1. Преобразует доменный Request в payload через toPayload()
     * 2. Создает URI из baseUri и ENDPOINT
     * 3. Создает PSR-7 Request с методом POST
     * 4. Устанавливает необходимые заголовки:
     *    - Authorization: Bearer {apiKey}
     *    - Content-Type: application/json
     * 5. Устанавливает тело запроса (JSON encoded payload)
     * 
     * @param Request $turnRequest Доменный запрос с сообщениями и настройками
     * @param string $baseUri Базовый URI API (например, 'https://api.openai.com')
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
     *     ->setModel('gpt-4o')
     *     ->build();
     * 
     * $psrRequest = $adapter->toPsrRequest(
     *     $request,
     *     'https://api.openai.com',
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

