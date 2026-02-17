<?php

namespace PvSource\Aivory\LLM;

use PvSource\Aivory\LLM\Turn\Request\Request;
use PvSource\Aivory\LLM\Turn\Response\ResponseInterface;

/**
 * Интерфейс провайдера AI
 * 
 * Определяет контракт для всех провайдеров AI (DeepSeek, OpenAI и т.д.).
 * Провайдер отвечает за преобразование доменных моделей в формат конкретного API
 * и обработку ответов от AI сервиса.
 * 
 * Принципы дизайна:
 * - Абстракция над различными AI провайдерами
 * - Единый интерфейс для работы с разными API
 * - Инкапсуляция специфики провайдера внутри реализации
 * - Работа с доменными моделями (Request, ResponseInterface), а не с HTTP деталями
 * 
 * Реализации:
 * - {@see \PvSource\Aivory\Providers\DeepSeek\DeepSeekProvider} - провайдер для DeepSeek API
 * - {@see \PvSource\Aivory\Providers\OpenAI\OpenAIProvider} - провайдер для OpenAI API
 * 
 * @package PvSource\Aivory\LLM
 * 
 * @example
 * // Получение провайдера через Client
 * $client = new Client('deepseek', ['apiKey' => 'sk-...']);
 * $provider = $client->getProvider();
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
 * // Использование в TurnBuilder
 * $turn = Turn::query()
 *     ->withProvider($provider)
 *     ->setUserPrompt('Вопрос')
 *     ->send();
 */
interface ProviderInterface
{
    /**
     * Отправляет запрос к AI провайдеру и возвращает ответ
     * 
     * Выполняет следующие действия:
     * 1. Преобразует доменный Request в формат конкретного API (через RequestAdapter)
     * 2. Отправляет HTTP запрос к API провайдера
     * 3. Обрабатывает HTTP ответ
     * 4. Преобразует ответ API в доменную модель ResponseInterface
     * 5. Возвращает ResponseInterface с данными ответа
     * 
     * Обработка ошибок:
     * - HTTP ошибки (4xx, 5xx) должны выбрасывать RuntimeException
     * - Ошибки парсинга JSON должны выбрасывать RuntimeException
     * - Некорректная структура ответа должна выбрасывать RuntimeException
     * 
     * @param Request $chatTurnRequest Доменный запрос с сообщениями и настройками
     * 
     * @return ResponseInterface Доменный ответ от AI провайдера
     * 
     * @throws \RuntimeException Если произошла ошибка при отправке запроса:
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
     *     ->setModel('gpt-4o')
     *     ->setResponseFormat(ResponseFormat::jsonSchema([
     *         'type' => MathSolution::class
     *     ]))
     *     ->build();
     * 
     * $response = $provider->sendChatTurn($request);
     * $mathSolution = $response->getSchemaObjects(); // Автоматическое преобразование в объект
     */
    public function sendChatTurn(
        Request $chatTurnRequest,
    ): ResponseInterface;
}