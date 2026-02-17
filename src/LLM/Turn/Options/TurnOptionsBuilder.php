<?php

namespace PvSource\Aivory\LLM\Turn\Options;

use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;

/**
 * Builder для создания TurnOptions
 * 
 * Позволяет пошагово настраивать параметры TurnOptions через fluent API.
 * Использует паттерн Builder для удобного создания сложных объектов конфигурации.
 * 
 * Все методы возвращают $this для поддержки method chaining (fluent API).
 * 
 * @package PvSource\Aivory\LLM\Turn\Options
 * 
 * @example
 * // Базовое использование
 * $options = (new TurnOptionsBuilder())
 *     ->setModel('deepseek-chat')
 *     ->setTemperature(0.7)
 *     ->setMaxTokens(2000)
 *     ->build();
 * 
 * @example
 * // Полная конфигурация
 * $options = (new TurnOptionsBuilder())
 *     ->setModel('gpt-4o')
 *     ->setTemperature(0.8)
 *     ->setMaxTokens(3000)
 *     ->setFrequencyPenalty(0.5)
 *     ->setPresencePenalty(0.3)
 *     ->setTopP(0.9)
 *     ->setResponseFormat(ResponseFormat::jsonSchema([...]))
 *     ->build();
 */
class TurnOptionsBuilder
{
    /**
     * Название модели AI
     * 
     * Примеры: 'deepseek-chat', 'gpt-4o', 'gpt-3.5-turbo'
     * 
     * @var string|null
     */
    private ?string $model = null;

    /**
     * Температура (креативность ответов)
     * 
     * Диапазон: 0.0 - 2.0
     * - 0.0 - детерминированные, предсказуемые ответы
     * - 1.0 - баланс между креативностью и точностью
     * - 2.0 - максимальная креативность
     * 
     * @var float|null
     */
    private ?float $temperature = null;

    /**
     * Максимальное количество токенов в ответе
     * 
     * Ограничивает длину ответа. Если null - используется значение по умолчанию провайдера.
     * 
     * @var int|null
     */
    private ?int $maxTokens = null;

    /**
     * Штраф за частоту (снижает повторения)
     * 
     * Диапазон: -2.0 - 2.0
     * - Положительное значение снижает вероятность повторения слов
     * - Отрицательное значение увеличивает вероятность повторений
     * - 0.0 - без штрафа
     * 
     * @var float|null
     */
    private ?float $frequencyPenalty = null;

    /**
     * Штраф за присутствие (поощряет новые темы)
     * 
     * Диапазон: -2.0 - 2.0
     * - Положительное значение поощряет обсуждение новых тем
     * - Отрицательное значение увеличивает вероятность повторения тем
     * - 0.0 - без штрафа
     * 
     * @var float|null
     */
    private ?float $presencePenalty = null;

    /**
     * Запоминать контекст между запросами
     * 
     * Если true - AI помнит предыдущие сообщения в диалоге.
     * Специфично для разных провайдеров.
     * 
     * @var bool|null
     */
    private ?bool $rememberContext = null;

    /**
     * Режим "мышления" (DeepSeek специфично)
     * 
     * Если true - AI показывает процесс размышления перед ответом.
     * Поддерживается только некоторыми провайдерами (например, DeepSeek).
     * 
     * @var bool|null
     */
    private ?bool $isThinking = null;

    /**
     * Формат ответа
     * 
     * Определяет формат, в котором AI должен вернуть ответ:
     * - ResponseFormat::TYPE_JSON_SCHEMA - структурированный JSON по схеме
     * - ResponseFormat::TYPE_TEXT - обычный текст
     * 
     * @var ResponseFormat|null
     */
    private ?ResponseFormat $responseFormat = null;

    /**
     * Массив строк для остановки генерации
     * 
     * Когда модель генерирует одну из этих строк, генерация останавливается.
     * Максимум 4 строки.
     * 
     * @var array<string>|null
     */
    private ?array $stop = null;

    /**
     * Включить streaming (потоковую передачу ответа)
     * 
     * Если true - ответ приходит частями в реальном времени.
     * Требует специальной обработки на стороне клиента.
     * 
     * @var bool|null
     */
    private ?bool $stream = null;

    /**
     * Опции для streaming
     * 
     * Дополнительные параметры для настройки потоковой передачи.
     * Структура зависит от провайдера.
     * 
     * @var array|null
     */
    private ?array $streamOptions = null;

    /**
     * Top-p (nucleus sampling)
     * 
     * Альтернатива temperature. Диапазон: 0.0 - 1.0
     * - 0.1 - рассматривает только топ 10% вероятностей
     * - 1.0 - рассматривает все токены
     * 
     * @var float|null
     */
    private ?float $topP = null;

    /**
     * Инструменты (tools) для function calling
     * 
     * Массив определений функций, которые модель может вызывать.
     * Структура зависит от провайдера.
     * 
     * @var array|null
     */
    private ?array $tools = null;

    /**
     * Выбор инструментов (tool choice)
     * 
     * Управляет тем, какие инструменты модель может использовать.
     * Возможные значения: 'none', 'auto', или объект с конкретным инструментом.
     * 
     * @var array|string|null
     */
    private array|string|null $toolChoice = null;

    /**
     * Включить логи вероятностей токенов
     * 
     * Если true - возвращает вероятности для каждого токена в ответе.
     * Увеличивает размер ответа.
     * 
     * @var bool|null
     */
    private ?bool $logprobs = null;

    /**
     * Количество топ логов вероятностей
     * 
     * Сколько токенов с наивысшей вероятностью возвращать для каждой позиции.
     * Диапазон: 0-20. Работает только если logprobs === true.
     * 
     * @var int|null
     */
    private ?int $topLogprobs = null;

    // ========== Геттеры ==========

    /**
     * Возвращает установленное название модели
     * 
     * @return string|null Название модели или null, если не установлено
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Возвращает установленную температуру
     * 
     * @return float|null Температура (0.0-2.0) или null, если не установлено
     */
    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    /**
     * Возвращает установленное максимальное количество токенов
     * 
     * @return int|null Максимальное количество токенов или null, если не установлено
     */
    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    /**
     * Возвращает установленный штраф за частоту
     * 
     * @return float|null Штраф за частоту (-2.0-2.0) или null, если не установлено
     */
    public function getFrequencyPenalty(): ?float
    {
        return $this->frequencyPenalty;
    }

    /**
     * Возвращает установленный штраф за присутствие
     * 
     * @return float|null Штраф за присутствие (-2.0-2.0) или null, если не установлено
     */
    public function getPresencePenalty(): ?float
    {
        return $this->presencePenalty;
    }

    /**
     * Возвращает установленный флаг запоминания контекста
     * 
     * @return bool|null true/false или null, если не установлено
     */
    public function getRememberContext(): ?bool
    {
        return $this->rememberContext;
    }

    /**
     * Возвращает установленный режим "мышления"
     * 
     * @return bool|null true/false или null, если не установлено
     */
    public function getIsThinking(): ?bool
    {
        return $this->isThinking;
    }

    /**
     * Возвращает установленный формат ответа
     * 
     * @return ResponseFormat|null Формат ответа или null, если не установлено
     */
    public function getResponseFormat(): ?ResponseFormat
    {
        return $this->responseFormat;
    }

    /**
     * Возвращает установленный массив строк для остановки генерации
     * 
     * @return array<string>|null Массив строк (максимум 4) или null, если не установлено
     */
    public function getStop(): ?array
    {
        return $this->stop;
    }

    /**
     * Возвращает установленный флаг streaming
     * 
     * @return bool|null true/false или null, если не установлено
     */
    public function getStream(): ?bool
    {
        return $this->stream;
    }

    /**
     * Возвращает установленные опции для streaming
     * 
     * @return array|null Опции для streaming или null, если не установлено
     */
    public function getStreamOptions(): ?array
    {
        return $this->streamOptions;
    }

    /**
     * Возвращает установленное значение Top-p
     * 
     * @return float|null Top-p (0.0-1.0) или null, если не установлено
     */
    public function getTopP(): ?float
    {
        return $this->topP;
    }

    /**
     * Возвращает установленные инструменты для function calling
     * 
     * @return array|null Массив определений функций или null, если не установлено
     */
    public function getTools(): ?array
    {
        return $this->tools;
    }

    /**
     * Возвращает установленный выбор инструментов
     * 
     * @return array|string|null 'none', 'auto', объект с инструментом или null, если не установлено
     */
    public function getToolChoice(): array|string|null
    {
        return $this->toolChoice;
    }

    /**
     * Возвращает установленный флаг логирования вероятностей
     * 
     * @return bool|null true/false или null, если не установлено
     */
    public function getLogprobs(): ?bool
    {
        return $this->logprobs;
    }

    /**
     * Возвращает установленное количество топ логов вероятностей
     * 
     * @return int|null Количество (0-20) или null, если не установлено
     */
    public function getTopLogprobs(): ?int
    {
        return $this->topLogprobs;
    }

    // ========== Сеттеры ==========

    /**
     * Устанавливает название модели AI
     * 
     * Модель определяет, какую AI модель использовать для генерации ответов.
     * Разные провайдеры поддерживают разные модели.
     * 
     * @param string $model Название модели (обязательно, не может быть пустой строкой)
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если model пустая строка
     * 
     * @example
     * // DeepSeek модели
     * $builder->setModel('deepseek-chat');
     * $builder->setModel('deepseek-coder');
     * 
     * @example
     * // OpenAI модели
     * $builder->setModel('gpt-4o');
     * $builder->setModel('gpt-4o-mini');
     * $builder->setModel('gpt-3.5-turbo');
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Устанавливает температуру (креативность ответов)
     * 
     * Контролирует случайность и креативность ответов модели.
     * 
     * Диапазон: 0.0 - 2.0
     * - 0.0 - детерминированные, предсказуемые ответы (хорошо для задач с одним правильным ответом)
     * - 0.7 - баланс между креативностью и точностью (рекомендуется для большинства задач)
     * - 1.0 - стандартное значение по умолчанию
     * - 2.0 - максимальная креативность, непредсказуемые ответы (для творческих задач)
     * 
     * @param float|null $temperature Температура (0.0-2.0). Если null - используется значение по умолчанию провайдера
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если значение вне диапазона 0.0-2.0
     * 
     * @example
     * // Для детерминированных ответов (математика, программирование)
     * $builder->setTemperature(0.1);
     * 
     * @example
     * // Для креативных задач (написание текстов, генерация идей)
     * $builder->setTemperature(1.2);
     */
    public function setTemperature(?float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    /**
     * Устанавливает максимальное количество токенов в ответе
     * 
     * Ограничивает длину ответа модели. Один токен примерно равен 0.75 слова на английском
     * или 1-2 символа на русском языке.
     * 
     * Если null - используется значение по умолчанию провайдера (обычно 4096 или больше).
     * 
     * @param int|null $maxTokens Максимальное количество токенов (должно быть положительным числом).
     *                            Если null - используется значение по умолчанию провайдера
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если значение <= 0
     * 
     * @example
     * // Короткие ответы
     * $builder->setMaxTokens(100);
     * 
     * @example
     * // Длинные ответы (статьи, подробные объяснения)
     * $builder->setMaxTokens(4000);
     */
    public function setMaxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    /**
     * Устанавливает штраф за частоту (снижает повторения)
     * 
     * Влияет на вероятность повторения токенов на основе их частоты в тексте.
     * 
     * Диапазон: -2.0 - 2.0
     * - Положительное значение (0.0-2.0) снижает вероятность повторения уже использованных слов
     * - Отрицательное значение (-2.0-0.0) увеличивает вероятность повторений
     * - 0.0 - без штрафа (по умолчанию)
     * 
     * Полезно для:
     * - Снижения повторений в длинных текстах
     * - Увеличения разнообразия ответов
     * 
     * @param float|null $frequencyPenalty Штраф за частоту (-2.0-2.0). Если null - используется 0.0
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если значение вне диапазона -2.0-2.0
     * 
     * @example
     * // Снизить повторения в длинных ответах
     * $builder->setFrequencyPenalty(0.5);
     * 
     * @example
     * // Увеличить повторения (редко используется)
     * $builder->setFrequencyPenalty(-0.5);
     */
    public function setFrequencyPenalty(?float $frequencyPenalty): self
    {
        $this->frequencyPenalty = $frequencyPenalty;
        return $this;
    }

    /**
     * Устанавливает штраф за присутствие (поощряет новые темы)
     * 
     * Влияет на вероятность появления новых тем в ответе на основе того,
     * присутствовали ли они уже в тексте.
     * 
     * Диапазон: -2.0 - 2.0
     * - Положительное значение (0.0-2.0) поощряет обсуждение новых тем
     * - Отрицательное значение (-2.0-0.0) увеличивает вероятность повторения тем
     * - 0.0 - без штрафа (по умолчанию)
     * 
     * Полезно для:
     * - Поощрения разнообразия тем в ответах
     * - Избежания зацикливания на одной теме
     * 
     * @param float|null $presencePenalty Штраф за присутствие (-2.0-2.0). Если null - используется 0.0
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если значение вне диапазона -2.0-2.0
     * 
     * @example
     * // Поощрить обсуждение новых тем
     * $builder->setPresencePenalty(0.3);
     */
    public function setPresencePenalty(?float $presencePenalty): self
    {
        $this->presencePenalty = $presencePenalty;
        return $this;
    }

    /**
     * Устанавливает флаг запоминания контекста между запросами
     * 
     * Если true - AI помнит предыдущие сообщения в диалоге и может использовать их
     * для более контекстуальных ответов.
     * 
     * Специфично для разных провайдеров. Некоторые провайдеры могут игнорировать этот параметр
     * или реализовывать его по-своему.
     * 
     * @param bool|null $rememberContext true - запоминать контекст, false - не запоминать.
     *                                   Если null - используется значение по умолчанию провайдера
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Включить запоминание контекста для диалога
     * $builder->setRememberContext(true);
     */
    public function setRememberContext(?bool $rememberContext): self
    {
        $this->rememberContext = $rememberContext;
        return $this;
    }

    /**
     * Устанавливает режим "мышления" (DeepSeek специфично)
     * 
     * Если true - AI показывает процесс размышления перед ответом.
     * Это позволяет увидеть логику рассуждений модели.
     * 
     * Поддерживается только некоторыми провайдерами (например, DeepSeek).
     * Для провайдеров, которые не поддерживают этот режим (например, OpenAI),
     * параметр игнорируется.
     * 
     * @param bool|null $isThinking true - включить режим мышления, false - выключить.
     *                             Если null - используется значение по умолчанию провайдера
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Включить режим мышления для DeepSeek
     * $builder->setIsThinking(true);
     */
    public function setIsThinking(?bool $isThinking): self
    {
        $this->isThinking = $isThinking;
        return $this;
    }

    /**
     * Устанавливает формат ответа
     * 
     * Определяет формат, в котором AI должен вернуть ответ:
     * - ResponseFormat::TYPE_JSON_SCHEMA - структурированный JSON по схеме
     * - ResponseFormat::TYPE_TEXT - обычный текст
     * 
     * Для JSON Schema можно использовать классы, реализующие JsonSchemaInterface,
     * что позволяет автоматически генерировать схемы и преобразовывать ответы в типизированные объекты.
     * 
     * @param ResponseFormat|null $responseFormat Формат ответа. Если null - используется текстовый формат
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Текстовый формат (по умолчанию)
     * $builder->setResponseFormat(ResponseFormat::text());
     * 
     * @example
     * // JSON Schema с ручной схемой
     * $builder->setResponseFormat(ResponseFormat::jsonSchema([
     *     'type' => 'object',
     *     'properties' => [
     *         'answer' => ['type' => 'string']
     *     ]
     * ]));
     * 
     * @example
     * // JSON Schema с использованием класса
     * $builder->setResponseFormat(ResponseFormat::jsonSchema([
     *     'type' => MathSolution::class
     * ]));
     */
    public function setResponseFormat(?ResponseFormat $responseFormat): self
    {
        $this->responseFormat = $responseFormat;
        return $this;
    }

    /**
     * Устанавливает массив строк для остановки генерации
     * 
     * Когда модель генерирует одну из этих строк, генерация останавливается.
     * Полезно для контроля длины ответа или остановки на определенных маркерах.
     * 
     * Ограничения:
     * - Максимум 4 строки
     * - Строки должны быть непустыми
     * 
     * @param array<string>|null $stop Массив строк для остановки (максимум 4 элемента).
     *                                Если null - остановка по умолчанию (конец ответа)
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если массив содержит более 4 элементов
     * 
     * @example
     * // Остановка на двойном переносе строки
     * $builder->setStop(['\n\n']);
     * 
     * @example
     * // Остановка на специальном маркере
     * $builder->setStop(['END', 'STOP', '###']);
     */
    public function setStop(?array $stop): self
    {
        $this->stop = $stop;
        return $this;
    }

    /**
     * Устанавливает флаг streaming (потоковой передачи ответа)
     * 
     * Если true - ответ приходит частями в реальном времени, а не одним блоком.
     * Это позволяет показывать ответ пользователю по мере генерации.
     * 
     * Требует специальной обработки на стороне клиента для работы с потоковыми данными.
     * 
     * @param bool|null $stream true - включить streaming, false - отключить.
     *                          Если null - используется значение по умолчанию провайдера (обычно false)
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Включить потоковую передачу для интерактивного интерфейса
     * $builder->setStream(true);
     */
    public function setStream(?bool $stream): self
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * Устанавливает опции для streaming
     * 
     * Дополнительные параметры для настройки потоковой передачи.
     * Структура зависит от провайдера и может включать:
     * - include_usage - включать информацию об использовании токенов
     * - chunk_format - формат чанков
     * - и другие провайдер-специфичные опции
     * 
     * @param array|null $streamOptions Опции для streaming. Если null - используются значения по умолчанию
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Включить информацию об использовании токенов в потоке
     * $builder->setStreamOptions(['include_usage' => true]);
     */
    public function setStreamOptions(?array $streamOptions): self
    {
        $this->streamOptions = $streamOptions;
        return $this;
    }

    /**
     * Устанавливает Top-p (nucleus sampling)
     * 
     * Альтернатива temperature для контроля случайности.
     * Выбирает токены из минимального набора, сумма вероятностей которых >= topP.
     * 
     * Диапазон: 0.0 - 1.0
     * - 0.1 - рассматривает только топ 10% наиболее вероятных токенов (детерминированно)
     * - 0.9 - рассматривает 90% наиболее вероятных токенов (более разнообразно)
     * - 1.0 - рассматривает все токены (максимальное разнообразие)
     * 
     * Обычно используется вместе с temperature или вместо неё.
     * 
     * @param float|null $topP Top-p (0.0-1.0). Если null - используется значение по умолчанию провайдера
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если значение вне диапазона 0.0-1.0
     * 
     * @example
     * // Для более детерминированных ответов
     * $builder->setTopP(0.1);
     * 
     * @example
     * // Для более разнообразных ответов
     * $builder->setTopP(0.9);
     */
    public function setTopP(?float $topP): self
    {
        $this->topP = $topP;
        return $this;
    }

    /**
     * Устанавливает инструменты (tools) для function calling
     * 
     * Позволяет модели вызывать внешние функции во время генерации ответа.
     * Модель может решить, когда и какие функции вызывать на основе контекста.
     * 
     * Массив должен содержать определения функций в формате, специфичном для провайдера.
     * Обычно включает:
     * - type: "function"
     * - function: { name, description, parameters (JSON Schema) }
     * 
     * @param array|null $tools Массив определений функций. Если null - функции не используются
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Определение функции для получения погоды
     * $builder->setTools([
     *     [
     *         'type' => 'function',
     *         'function' => [
     *             'name' => 'get_weather',
     *             'description' => 'Получить текущую погоду в городе',
     *             'parameters' => [
     *                 'type' => 'object',
     *                 'properties' => [
     *                     'city' => ['type' => 'string']
     *                 ]
     *             ]
     *         ]
     *     ]
     * ]);
     */
    public function setTools(?array $tools): self
    {
        $this->tools = $tools;
        return $this;
    }

    /**
     * Устанавливает выбор инструментов (tool choice)
     * 
     * Управляет тем, какие инструменты модель может использовать.
     * 
     * Возможные значения:
     * - 'none' - модель не может вызывать функции (даже если они определены)
     * - 'auto' - модель сама решает, вызывать ли функции (по умолчанию)
     * - объект с конкретным инструментом - модель должна вызвать указанную функцию
     * 
     * @param array|string|null $toolChoice Выбор инструментов:
     *                                      - 'none' - не использовать инструменты
     *                                      - 'auto' - автоматический выбор (по умолчанию)
     *                                      - объект с конкретным инструментом
     *                                      - null - используется 'auto'
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Автоматический выбор инструментов
     * $builder->setToolChoice('auto');
     * 
     * @example
     * // Запретить использование инструментов
     * $builder->setToolChoice('none');
     * 
     * @example
     * // Принудительно вызвать конкретную функцию
     * $builder->setToolChoice([
     *     'type' => 'function',
     *     'function' => ['name' => 'get_weather']
     * ]);
     */
    public function setToolChoice(array|string|null $toolChoice): self
    {
        $this->toolChoice = $toolChoice;
        return $this;
    }

    /**
     * Устанавливает флаг логирования вероятностей токенов
     * 
     * Если true - возвращает вероятности для каждого токена в ответе.
     * Это позволяет анализировать уверенность модели в каждом слове.
     * 
     * Увеличивает размер ответа, так как для каждого токена добавляется информация о вероятностях.
     * 
     * Для работы с topLogprobs необходимо установить logprobs в true.
     * 
     * @param bool|null $logprobs true - включить логи вероятностей, false - выключить.
     *                            Если null - используется значение по умолчанию провайдера (обычно false)
     * @return self Для поддержки method chaining
     * 
     * @example
     * // Включить логи вероятностей для анализа уверенности модели
     * $builder->setLogprobs(true);
     */
    public function setLogprobs(?bool $logprobs): self
    {
        $this->logprobs = $logprobs;
        return $this;
    }

    /**
     * Устанавливает количество топ логов вероятностей
     * 
     * Определяет, сколько токенов с наивысшей вероятностью возвращать для каждой позиции.
     * Полезно для анализа альтернативных вариантов, которые модель рассматривала.
     * 
     * Диапазон: 0-20
     * - 0 - только выбранный токен
     * - 5 - топ 5 альтернативных вариантов
     * - 20 - максимум альтернативных вариантов
     * 
     * Работает только если logprobs === true.
     * 
     * @param int|null $topLogprobs Количество топ логов (0-20). Если null - используется значение по умолчанию
     * @return self Для поддержки method chaining
     * 
     * @throws \InvalidArgumentException Если значение вне диапазона 0-20
     * @throws \InvalidArgumentException Если logprobs !== true
     * 
     * @example
     * // Получить топ 5 альтернативных вариантов для каждого токена
     * $builder->setLogprobs(true);
     * $builder->setTopLogprobs(5);
     */
    public function setTopLogprobs(?int $topLogprobs): self
    {
        $this->topLogprobs = $topLogprobs;
        return $this;
    }

    /**
     * Создает объект TurnOptions на основе установленных значений
     * 
     * Выполняет финальную валидацию всех параметров и создает неизменяемый объект TurnOptions.
     * 
     * Обязательные параметры:
     * - model - должен быть установлен
     * 
     * Валидация:
     * - temperature: 0.0-2.0
     * - maxTokens: > 0
     * - frequencyPenalty: -2.0-2.0
     * - presencePenalty: -2.0-2.0
     * - stop: максимум 4 элемента
     * - topP: 0.0-1.0
     * - topLogprobs: 0-20 (требует logprobs === true)
     * 
     * @return TurnOptions Неизменяемый объект с настройками
     * 
     * @throws \InvalidArgumentException Если model не установлен
     * @throws \InvalidArgumentException Если значения вне допустимого диапазона
     * 
     * @example
     * // Минимальная конфигурация
     * $options = (new TurnOptionsBuilder())
     *     ->setModel('deepseek-chat')
     *     ->build();
     * 
     * @example
     * // Полная конфигурация
     * $options = (new TurnOptionsBuilder())
     *     ->setModel('gpt-4o')
     *     ->setTemperature(0.7)
     *     ->setMaxTokens(2000)
     *     ->setFrequencyPenalty(0.5)
     *     ->setPresencePenalty(0.3)
     *     ->setTopP(0.9)
     *     ->setResponseFormat(ResponseFormat::jsonSchema([...]))
     *     ->build();
     */
    public function build(): TurnOptions
    {
        if ($this->model === null) {
            throw new \InvalidArgumentException('Model is required. Use setModel() to set it.');
        }

        return new TurnOptions(
            model: $this->model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            frequencyPenalty: $this->frequencyPenalty,
            presencePenalty: $this->presencePenalty,
            rememberContext: $this->rememberContext,
            isThinking: $this->isThinking,
            responseFormat: $this->responseFormat,
            stop: $this->stop,
            stream: $this->stream,
            streamOptions: $this->streamOptions,
            topP: $this->topP,
            tools: $this->tools,
            toolChoice: $this->toolChoice,
            logprobs: $this->logprobs,
            topLogprobs: $this->topLogprobs,
        );
    }
}
