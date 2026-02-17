<?php

namespace PvSource\Aivory\LLM\Turn\Request;

use PvSource\Aivory\LLM\Turn\Message\MessageCollection;
use PvSource\Aivory\LLM\Turn\Options\TurnOptions;

/**
 * Request - доменный DTO (Data Transfer Object) для запроса к AI провайдеру
 * 
 * Представляет неизменяемый (immutable) объект запроса, содержащий все необходимые данные
 * для отправки запроса к AI провайдеру.
 * 
 * Принципы дизайна:
 * - Содержит только доменные данные (messages, options)
 * - Не содержит специфики провайдеров или HTTP логики
 * - Неизменяемый (readonly) - гарантирует целостность данных
 * - Преобразование в HTTP запрос выполняется через RequestAdapter в Infrastructure слое
 * 
 * Использование:
 * Обычно создается через RequestBuilder, который предоставляет удобный fluent API
 * для пошаговой настройки параметров запроса.
 * 
 * @package PvSource\Aivory\LLM\Turn
 * 
 * @example
 * // Создание через RequestBuilder (рекомендуемый способ)
 * $request = (new RequestBuilder())
 *     ->setUserPrompt('Привет')
 *     ->setModel('deepseek-chat')
 *     ->setTemperature(0.7)
 *     ->build();
 * 
 * @example
 * // Прямое создание (редко используется)
 * $messages = new MessageCollection([
 *     Message::user('Привет')
 * ]);
 * $options = new TurnOptions(model: 'deepseek-chat');
 * $request = new Request($messages, $options);
 * 
 * @example
 * // Использование в провайдере
 * $response = $provider->sendChatTurn($request);
 */
readonly class Request
{
    /**
     * Коллекция сообщений для диалога
     * 
     * Содержит все сообщения, которые будут отправлены провайдеру:
     * - Системные сообщения (system) - инструкции для AI
     * - Сообщения пользователя (user) - вопросы и запросы
     * - Сообщения ассистента (assistant) - предыдущие ответы (для контекста)
     * 
     * Порядок сообщений важен - они отправляются в том порядке, в котором добавлены.
     * 
     * @var MessageCollection
     */
    private MessageCollection $messages;

    /**
     * Настройки запроса (опции)
     * 
     * Содержит все параметры конфигурации для запроса:
     * - Модель AI
     * - Параметры генерации (temperature, maxTokens, и т.д.)
     * - Формат ответа (ResponseFormat)
     * - И другие настройки
     * 
     * @var TurnOptions
     */
    private TurnOptions $turnOptions;

    /**
     * Создает новый объект Request
     * 
     * @param MessageCollection $messages Коллекция сообщений для диалога (не может быть пустой)
     * @param TurnOptions $turnOptions Настройки запроса (обязательно, должен содержать model)
     * 
     * @throws \InvalidArgumentException Если messages пуста (проверка выполняется в RequestBuilder)
     * @throws \InvalidArgumentException Если turnOptions.model не установлен (проверка выполняется в TurnOptions)
     * 
     * @example
     * $messages = new MessageCollection([
     *     Message::system('Ты помощник'),
     *     Message::user('Привет')
     * ]);
     * $options = new TurnOptions(model: 'deepseek-chat');
     * $request = new Request($messages, $options);
     */
    public function __construct(
        MessageCollection $messages,
        TurnOptions $turnOptions
    ) {
        $this->messages = $messages;
        $this->turnOptions = $turnOptions;
    }

    /**
     * Возвращает коллекцию сообщений
     * 
     * @return MessageCollection Коллекция сообщений для диалога
     * 
     * @example
     * $messages = $request->getMessages();
     * foreach ($messages as $message) {
     *     echo "[{$message->role}]: {$message->content}\n";
     * }
     */
    public function getMessages(): MessageCollection
    {
        return $this->messages;
    }

    /**
     * Возвращает настройки запроса
     * 
     * @return TurnOptions Настройки запроса (модель, параметры генерации, формат ответа и т.д.)
     * 
     * @example
     * $options = $request->getOptions();
     * echo "Модель: {$options->model}\n";
     * echo "Температура: " . ($options->temperature ?? 'не указана') . "\n";
     * 
     * @example
     * // Получение ResponseFormat для обработки ответа
     * $responseFormat = $request->getOptions()->responseFormat;
     * if ($responseFormat !== null) {
     *     echo "Формат ответа: {$responseFormat->type}\n";
     * }
     */
    public function getOptions(): TurnOptions
    {
        return $this->turnOptions;
    }
}

