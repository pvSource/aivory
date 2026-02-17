<?php

namespace PvSource\Aivory\Transport;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Интерфейс HTTP клиента
 * 
 * Определяет контракт для отправки HTTP запросов в библиотеке.
 * Абстрагирует конкретную реализацию HTTP клиента (Guzzle, cURL и т.д.)
 * и позволяет использовать любую PSR-7 совместимую реализацию.
 * 
 * Принципы дизайна:
 * - Абстракция над HTTP клиентами
 * - Работа с PSR-7 Request и Response
 * - Позволяет легко заменить реализацию HTTP клиента
 * - Инкапсулирует детали работы с сетью
 * 
 * Реализации:
 * - {@see \PvSource\Aivory\Transport\GuzzleHttpClient} - реализация через Guzzle HTTP Client
 * 
 * @package PvSource\Aivory\Transport
 * 
 * @example
 * // Использование в провайдере
 * $psrRequest = $requestAdapter->toPsrRequest($turnRequest, $baseUri, $apiKey);
 * $httpResponse = $httpClient->sendRequest($psrRequest);
 * 
 * @example
 * // Создание кастомного HTTP клиента
 * class CustomHttpClient implements HttpClientInterface {
 *     public function sendRequest(RequestInterface $request): ResponseInterface {
 *         // Кастомная реализация
 *     }
 * }
 * 
 * $client = new Client('deepseek', ['apiKey' => 'sk-...'], new CustomHttpClient());
 */
interface HttpClientInterface
{
    /**
     * Отправляет HTTP запрос и возвращает ответ
     * 
     * Выполняет синхронную отправку HTTP запроса и возвращает ответ.
     * Реализация должна обрабатывать:
     * - Сетевые ошибки
     * - Таймауты
     * - HTTP ошибки (4xx, 5xx)
     * 
     * @param RequestInterface $request PSR-7 Request для отправки
     * 
     * @return ResponseInterface PSR-7 Response с ответом от сервера
     * 
     * @throws \Psr\Http\Client\ClientExceptionInterface Если произошла ошибка при отправке запроса:
     *                                                   - Сетевые ошибки
     *                                                   - Таймауты
     *                                                   - Проблемы с соединением
     * 
     * @example
     * // Базовое использование
     * $psrRequest = $requestFactory->createRequest('POST', $uri)
     *     ->withHeader('Authorization', "Bearer {$apiKey}")
     *     ->withBody($streamFactory->createStream(json_encode($payload)));
     * 
     * $response = $httpClient->sendRequest($psrRequest);
     * $statusCode = $response->getStatusCode();
     * $body = $response->getBody()->getContents();
     * 
     * @example
     * // С обработкой ошибок
     * try {
     *     $response = $httpClient->sendRequest($request);
     *     // Обработка успешного ответа
     * } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
     *     // Обработка ошибок сети
     *     echo "Ошибка: " . $e->getMessage();
     * }
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}