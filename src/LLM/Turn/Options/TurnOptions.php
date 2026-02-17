<?php

namespace PvSource\Aivory\LLM\Turn\Options;

use InvalidArgumentException;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;

/**
 * Настройки чата для AI провайдера.
 *
 * Содержит универсальные параметры, которые поддерживаются большинством провайдеров.
 * Специфичные параметры могут быть проигнорированы провайдерами, которые их не поддерживают или заменены с использованием кастомной реализации.
 *
 * @package PvSource\Aivory\Domain
 *
 * @example
 * // Минимальная конфигурация
 * $options = new TurnOptions(model: 'deepseek-chat');
 *
 * @example
 * // Полная конфигурация
 * $options = new TurnOptions(
 *     model: 'deepseek-chat',
 *     temperature: 0.7,
 *     maxTokens: 2000,
 *     frequencyPenalty: 0.5,
 *     presencePenalty: 0.3,
 *     rememberContext: true,
 *     isThinking: false
 * );
 *
 * @example
 * // Объединение настроек
 * $default = new TurnOptions(model: 'deepseek-chat', temperature: 1.0);
 * $override = new TurnOptions(model: 'deepseek-chat', temperature: 0.7);
 * $merged = $default->merge($override);
 */
class TurnOptions
{
    /**
     * Название модели AI.
     *
     * Примеры: 'deepseek-chat', 'gpt-4', 'claude-3-opus'
     *
     * @var string
     * @readonly
     */
    public readonly string $model;

    /**
     * Температура (креативность ответов).
     *
     * Диапазон: 0.0 - 2.0
     * - 0.0 - детерминированные, предсказуемые ответы
     * - 1.0 - баланс между креативностью и точностью (по умолчанию)
     * - 2.0 - максимальная креативность, непредсказуемые ответы
     *
     * @var float|null
     * @readonly
     */
    public readonly ?float $temperature;

    /**
     * Максимальное количество токенов в ответе.
     *
     * Ограничивает длину ответа. Если null - используется
     * значение по умолчанию провайдера.
     *
     * @var int|null
     * @readonly
     */
    public readonly ?int $maxTokens;

    /**
     * Штраф за частоту (снижает повторения).
     *
     * Диапазон: -2.0 - 2.0
     * - Положительное значение снижает вероятность повторения слов
     * - Отрицательное значение увеличивает вероятность повторений
     * - 0.0 - без штрафа (по умолчанию)
     *
     * @var float|null
     * @readonly
     */
    public readonly ?float $frequencyPenalty;

    /**
     * Штраф за присутствие (поощряет новые темы).
     *
     * Диапазон: -2.0 - 2.0
     * - Положительное значение поощряет обсуждение новых тем
     * - Отрицательное значение увеличивает вероятность повторения тем
     * - 0.0 - без штрафа (по умолчанию)
     *
     * @var float|null
     * @readonly
     */
    public readonly ?float $presencePenalty;

    /**
     * Запоминать контекст между запросами.
     *
     * Если true - AI помнит предыдущие сообщения в диалоге.
     * Специфично для разных провайдеров и имеет обертки в реализации над реальным функционалом нейросетей.
     *
     * @var bool|null
     * @readonly
     */
    public readonly ?bool $rememberContext;

    /**
     * Режим "мышления" (DeepSeek специфично).
     *
     * Если true - AI показывает процесс размышления перед ответом.
     * Поддерживается только некоторыми провайдерами (например, DeepSeek).
     *
     * @var bool|null
     * @readonly
     */
    public readonly ?bool $isThinking;

    /**
     * Формат ответа
     *
     * @var ResponseFormat|null
     * @readonly
     */
    public readonly ?ResponseFormat $responseFormat;

    /**
     * Массив строк для остановки генерации.
     *
     * Когда модель генерирует одну из этих строк, генерация останавливается.
     * Максимум 4 строки.
     *
     * @var array<string>|null
     * @readonly
     */
    public readonly ?array $stop;

    /**
     * Включить streaming (потоковую передачу ответа).
     *
     * Если true - ответ приходит частями в реальном времени.
     * Требует специальной обработки на стороне клиента.
     *
     * @var bool|null
     * @readonly
     */
    public readonly ?bool $stream;

    /**
     * Опции для streaming.
     *
     * Дополнительные параметры для настройки потоковой передачи.
     * Структура зависит от провайдера.
     *
     * @var array|null
     * @readonly
     */
    public readonly ?array $streamOptions;

    /**
     * Top-p (nucleus sampling).
     *
     * Альтернатива temperature. Диапазон: 0.0 - 1.0
     * - 0.1 - рассматривает только топ 10% вероятностей
     * - 1.0 - рассматривает все токены (по умолчанию)
     *
     * @var float|null
     * @readonly
     */
    public readonly ?float $topP;

    /**
     * Инструменты (tools) для function calling.
     *
     * Массив определений функций, которые модель может вызывать.
     * Структура зависит от провайдера.
     *
     * @var array|null
     * @readonly
     */
    public readonly ?array $tools;

    /**
     * Выбор инструментов (tool choice).
     *
     * Управляет тем, какие инструменты модель может использовать.
     * Возможные значения: 'none', 'auto', или объект с конкретным инструментом.
     *
     * @var array|string|null
     * @readonly
     */
    public readonly array|string|null $toolChoice;

    /**
     * Включить логи вероятностей токенов.
     *
     * Если true - возвращает вероятности для каждого токена в ответе.
     * Увеличивает размер ответа.
     *
     * @var bool|null
     * @readonly
     */
    public readonly ?bool $logprobs;

    /**
     * Количество топ логов вероятностей.
     *
     * Сколько токенов с наивысшей вероятностью возвращать для каждого позиции.
     * Диапазон: 0-20. Работает только если logprobs === true.
     *
     * @var int|null
     * @readonly
     */
    public readonly ?int $topLogprobs;

    /**
     * Создает новые настройки чата.
     *
     * @param string $model Название модели (обязательно)
     * @param float|null $temperature Температура (0.0-2.0, по умолчанию 1.0)
     * @param int|null $maxTokens Максимальное количество токенов (положительное число)
     * @param float|null $frequencyPenalty Штраф за частоту (-2.0-2.0, по умолчанию 0.0)
     * @param float|null $presencePenalty Штраф за присутствие (-2.0-2.0, по умолчанию 0.0)
     * @param bool|null $rememberContext Запоминать контекст (специфично для провайдера)
     * @param bool|null $isThinking Режим мышления (DeepSeek специфично, по умолчанию false)
     * @param ResponseFormat|null $responseFormat Формат ответа (Для большинства провайдеров, по умолчанию text)
     * @param array<string>|null $stop Массив строк для остановки генерации (максимум 4)
     * @param bool|null $stream Включить streaming
     * @param array|null $streamOptions Опции для streaming
     * @param float|null $topP Top-p sampling (0.0-1.0)
     * @param array|null $tools Инструменты для function calling
     * @param array|string|null $toolChoice Выбор инструментов ('none', 'auto', или объект)
     * @param bool|null $logprobs Включить логи вероятностей
     * @param int|null $topLogprobs Количество топ логов вероятностей (0-20)
     *
     * @throws InvalidArgumentException Если значения вне допустимого диапазона
     *
     * @example
     * $options = new TurnOptions(
     *     model: 'deepseek-chat',
     *     temperature: 0.7,
     *     maxTokens: 2000
     * );
     */
    public function __construct(
        string $model,
        ?float $temperature = 1.0,
        ?int $maxTokens = null,
        ?float $frequencyPenalty = 0.0,
        ?float $presencePenalty = 0.0,
        ?bool $rememberContext = null,
        ?bool $isThinking = false,
        ?ResponseFormat $responseFormat = null,
        ?array $stop = null,
        ?bool $stream = null,
        ?array $streamOptions = null,
        ?float $topP = null,
        ?array $tools = null,
        array|string|null $toolChoice = null,
        ?bool $logprobs = null,
        ?int $topLogprobs = null
    ) {
        // Валидация model
        if (trim($model) === '') {
            throw new InvalidArgumentException("Model cannot be empty: $model");
        }

        // Валидация temperature
        if ($temperature !== null && ($temperature < 0.0 || $temperature > 2.0)) {
            throw new InvalidArgumentException("Temperature must be between 0.0 and 2.0, got: $temperature");
        }

        // Валидация maxTokens
        if ($maxTokens !== null && $maxTokens <= 0) {
            throw new InvalidArgumentException("MaxTokens must be positive, got: $maxTokens");
        }

        // Валидация frequencyPenalty
        if ($frequencyPenalty !== null && ($frequencyPenalty < -2.0 || $frequencyPenalty > 2.0)) {
            throw new InvalidArgumentException("FrequencyPenalty must be between -2.0 and 2.0, got: $frequencyPenalty");
        }

        // Валидация presencePenalty
        if ($presencePenalty !== null && ($presencePenalty < -2.0 || $presencePenalty > 2.0)) {
            throw new InvalidArgumentException("PresencePenalty must be between -2.0 and 2.0, got: $presencePenalty");
        }

        // Валидация stop
        if ($stop !== null && count($stop) > 4) {
            throw new InvalidArgumentException("Stop array must contain at most 4 strings, got: " . count($stop));
        }

        // Валидация topP
        if ($topP !== null && ($topP < 0.0 || $topP > 1.0)) {
            throw new InvalidArgumentException("TopP must be between 0.0 and 1.0, got: $topP");
        }

        // Валидация topLogprobs
        if ($topLogprobs !== null && ($topLogprobs < 0 || $topLogprobs > 20)) {
            throw new InvalidArgumentException("TopLogprobs must be between 0 and 20, got: $topLogprobs");
        }

        // Валидация topLogprobs требует logprobs
        if ($topLogprobs !== null && $logprobs !== true) {
            throw new InvalidArgumentException("TopLogprobs requires logprobs to be true");
        }

        $this->model = $model;
        $this->temperature = $temperature;
        $this->maxTokens = $maxTokens;
        $this->frequencyPenalty = $frequencyPenalty;
        $this->presencePenalty = $presencePenalty;
        $this->rememberContext = $rememberContext;
        $this->isThinking = $isThinking;
        $this->responseFormat = $responseFormat;
        $this->stop = $stop;
        $this->stream = $stream;
        $this->streamOptions = $streamOptions;
        $this->topP = $topP;
        $this->tools = $tools;
        $this->toolChoice = $toolChoice;
        $this->logprobs = $logprobs;
        $this->topLogprobs = $topLogprobs;
    }

    /**
     * Объединяет настройки с переопределениями.
     *
     * Создает новый объект TurnOptions, где значения из $override заменяют соответствующие значения из текущего объекта.
     * Если в $override значение null, используется значение из текущего объекта. Если не null - используется значение из $override.
     *
     * @param TurnOptions $override Настройки для переопределения
     *
     * @return TurnOptions Новый объект с объединенными настройками
     *
     * @example
     * $default = new TurnOptions(
     *     model: 'deepseek-chat',
     *     temperature: 1.0,
     *     maxTokens: 3000
     * );
     *
     * $override = new TurnOptions(
     *     temperature: 0.7
     * );
     *
     * $merged = $default->merge($override);
     * // $merged->model === 'deepseek-chat' (из default)
     * // $merged->temperature === 0.7 (из override)
     * // $merged->maxTokens === 3000 (из default)
     */
    public function merge(TurnOptions $override): TurnOptions
    {
        return new TurnOptions(
            model: $override->model ?? $this->model,
            temperature: $override->temperature ?? $this->temperature,
            maxTokens: $override->maxTokens ?? $this->maxTokens,
            frequencyPenalty: $override->frequencyPenalty ?? $this->frequencyPenalty,
            presencePenalty: $override->presencePenalty ?? $this->presencePenalty,
            rememberContext: $override->rememberContext ?? $this->rememberContext,
            isThinking: $override->isThinking ?? $this->isThinking,
            responseFormat: $override->responseFormat ?? $this->responseFormat,
            stop: $override->stop ?? $this->stop,
            stream: $override->stream ?? $this->stream,
            streamOptions: $override->streamOptions ?? $this->streamOptions,
            topP: $override->topP ?? $this->topP,
            tools: $override->tools ?? $this->tools,
            toolChoice: $override->toolChoice ?? $this->toolChoice,
            logprobs: $override->logprobs ?? $this->logprobs,
            topLogprobs: $override->topLogprobs ?? $this->topLogprobs,
        );
    }
}