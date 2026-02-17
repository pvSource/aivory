<?php

namespace PvSource\Aivory\Transport;

use GuzzleHttp\Client as GuzzleClient;

use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * Реализация HTTP клиента через Guzzle
 * 
 * Обертка над Guzzle HTTP Client, реализующая HttpClientInterface.
 * Используется по умолчанию в библиотеке для отправки HTTP запросов к AI провайдерам.
 * 
 * Особенности:
 * - Использует Guzzle HTTP Client для отправки запросов
 * - Полностью совместим с PSR-7 Request и Response
 * - Поддерживает все возможности Guzzle (прокси, таймауты, retry и т.д.)
 * - Простая обертка, делегирующая вызовы в Guzzle Client
 * 
 * Использование:
 * Обычно создается автоматически в Client с настройками по умолчанию,
 * но можно передать кастомный Guzzle Client с дополнительными настройками.
 * 
 * @package PvSource\Aivory\Transport
 * 
 * @see \PvSource\Aivory\Transport\HttpClientInterface Интерфейс, который реализует класс
 * @see \GuzzleHttp\Client Guzzle HTTP Client, используемый внутри
 * 
 * @example
 * // Создание с настройками по умолчанию (используется в Client)
 * $guzzleClient = new GuzzleClient(['timeout' => 600]);
 * $httpClient = new GuzzleHttpClient($guzzleClient);
 * 
 * @example
 * // С кастомными настройками (прокси, таймауты)
 * $guzzleClient = new GuzzleClient([
 *     'timeout' => 300,
 *     'proxy' => 'tcp://proxy.example.com:8080',
 *     'verify' => false, // Отключить SSL проверку (только для тестирования!)
 * ]);
 * $httpClient = new GuzzleHttpClient($guzzleClient);
 * 
 * $client = new Client('deepseek', ['apiKey' => 'sk-...'], $httpClient);
 * 
 * @example
 * // С retry middleware (через Guzzle middleware)
 * use GuzzleHttp\HandlerStack;
 * use GuzzleHttp\Middleware;
 * use GuzzleHttp\Psr7\Request;
 * 
 * $stack = HandlerStack::create();
 * $stack->push(Middleware::retry(function ($retries, Request $request, $response, $exception) {
 *     return $retries < 3 && ($exception !== null || $response->getStatusCode() >= 500);
 * }));
 * 
 * $guzzleClient = new GuzzleClient(['handler' => $stack]);
 * $httpClient = new GuzzleHttpClient($guzzleClient);
 */
class GuzzleHttpClient implements HttpClientInterface
{
    /**
     * Создает новый GuzzleHttpClient
     * 
     * @param GuzzleClient $client Guzzle HTTP Client для отправки запросов
     * 
     * @example
     * // Базовое использование
     * $guzzleClient = new GuzzleClient();
     * $httpClient = new GuzzleHttpClient($guzzleClient);
     * 
     * @example
     * // С настройками
     * $guzzleClient = new GuzzleClient([
     *     'timeout' => 600,
     *     'connect_timeout' => 30,
     * ]);
     * $httpClient = new GuzzleHttpClient($guzzleClient);
     */
    public function __construct(
        private GuzzleClient $client
    ) {}

    /**
     * Отправляет HTTP запрос через Guzzle Client
     * 
     * Делегирует отправку запроса в Guzzle HTTP Client.
     * Guzzle обрабатывает все детали: соединение, отправку, получение ответа.
     * 
     * @param PsrRequestInterface $request PSR-7 Request для отправки
     * 
     * @return PsrResponseInterface PSR-7 Response с ответом от сервера
     * 
     * @throws \GuzzleHttp\Exception\GuzzleException Если произошла ошибка при отправке запроса:
     *                                               - Сетевые ошибки
     *                                               - Таймауты
     *                                               - HTTP ошибки (4xx, 5xx)
     * 
     * @example
     * // Базовое использование
     * $psrRequest = $requestFactory->createRequest('POST', $uri)
     *     ->withHeader('Authorization', "Bearer {$apiKey}")
     *     ->withBody($streamFactory->createStream(json_encode($payload)));
     * 
     * $response = $httpClient->sendRequest($psrRequest);
     * 
     * @example
     * // С обработкой ошибок
     * try {
     *     $response = $httpClient->sendRequest($request);
     *     // Обработка успешного ответа
     * } catch (\GuzzleHttp\Exception\GuzzleException $e) {
     *     // Обработка ошибок
     *     echo "Ошибка: " . $e->getMessage();
     * }
     */
    public function sendRequest(PsrRequestInterface $request): PsrResponseInterface
    {
        return $this->client->sendRequest($request);
    }
}