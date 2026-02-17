<?php

namespace PvSource\Aivory\LLM\Turn;

use PvSource\Aivory\LLM\ProviderInterface;
use PvSource\Aivory\LLM\Turn\Message\Message;
use PvSource\Aivory\LLM\Turn\Request\RequestBuilder;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;

/**
 * Builder для создания Turn
 * 
 * Позволяет пошагово настраивать параметры Turn через fluent API.
 * Делегирует работу с запросами в RequestBuilder.
 * 
 * @example
 * $turn = MyTurn::query()
 *     ->setUserPrompt('Привет')
 *     ->setModel('deepseek-chat')
 *     ->setTemperature(0.7)
 *     ->build();
 */
class TurnBuilder
{
    private string $turnClass;
    protected RequestBuilder $requestBuilder;
    private ?ProviderInterface $provider = null;

    public function __construct(string $turnClass)
    {
        $this->turnClass = $turnClass;
        $this->requestBuilder = new RequestBuilder();

        // Получаем default значения из класса Turn через статические методы
        // Используем late static binding - если класс переопределил методы, будут вызваны его версии

        if ($defaultSystemPrompt = $turnClass::getDefaultSystemPrompt()) {
            $this->requestBuilder->addMessage(new Message(
                role: 'system',
                content: $defaultSystemPrompt
            ));
        }

        if ($defaultModel = $turnClass::getDefaultModel()) {
            $this->requestBuilder->setModel($defaultModel);
        }

        if ($defaultTemperature = $turnClass::getDefaultTemperature()) {
            $this->requestBuilder->setTemperature($defaultTemperature);
        }

        if ($defaultMaxTokens = $turnClass::getDefaultMaxTokens()) {
            $this->requestBuilder->setMaxTokens($defaultMaxTokens);
        }

        if ($defaultFrequencyPenalty = $turnClass::getDefaultFrequencyPenalty()) {
            $this->requestBuilder->setFrequencyPenalty($defaultFrequencyPenalty);
        }

        if ($defaultPresencePenalty = $turnClass::getDefaultPresencePenalty()) {
            $this->requestBuilder->setPresencePenalty($defaultPresencePenalty);
        }

        if (($defaultRememberContext = $turnClass::getDefaultRememberContext()) !== null) {
            $this->requestBuilder->setRememberContext($defaultRememberContext);
        }

        if (($defaultIsThinking = $turnClass::getDefaultIsThinking()) !== null) {
            $this->requestBuilder->setIsThinking($defaultIsThinking);
        }

        if ($defaultResponseFormat = $turnClass::getDefaultResponseFormat()) {
            $this->requestBuilder->setResponseFormat($defaultResponseFormat);
        }

        if ($defaultStop = $turnClass::getDefaultStop()) {
            $this->requestBuilder->setStop($defaultStop);
        }

        if (($defaultStream = $turnClass::getDefaultStream()) !== null) {
            $this->requestBuilder->setStream($defaultStream);
        }

        if ($defaultStreamOptions = $turnClass::getDefaultStreamOptions()) {
            $this->requestBuilder->setStreamOptions($defaultStreamOptions);
        }

        if ($defaultTopP = $turnClass::getDefaultTopP()) {
            $this->requestBuilder->setTopP($defaultTopP);
        }

        if ($defaultTools = $turnClass::getDefaultTools()) {
            $this->requestBuilder->setTools($defaultTools);
        }

        if ($defaultToolChoice = $turnClass::getDefaultToolChoice()) {
            $this->requestBuilder->setToolChoice($defaultToolChoice);
        }

        if (($defaultLogprobs = $turnClass::getDefaultLogprobs()) !== null) {
            $this->requestBuilder->setLogprobs($defaultLogprobs);
        }

        if ($defaultTopLogprobs = $turnClass::getDefaultTopLogprobs()) {
            $this->requestBuilder->setTopLogprobs($defaultTopLogprobs);
        }
    }

    /**
     * Возвращает билдер запросов
     * 
     * @return RequestBuilder
     */
    public function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }

    // ========== Делегирование методов RequestBuilder для сообщений ==========

    /**
     * Добавляет сообщение в коллекцию
     * 
     * @param string $role Роль сообщения
     * @param string $prompt Текст сообщения
     * @return self
     */
    public function withPrompt(string $role, string $prompt): self
    {
        $this->requestBuilder->setPrompt($role, $prompt);
        return $this;
    }

    /**
     * Добавляет сообщение пользователя
     * 
     * @param string $content Текст сообщения
     * @return self
     */
    public function setUserPrompt(string $content): self
    {
        $this->requestBuilder->setUserPrompt($content);
        return $this;
    }

    /**
     * Добавляет системное сообщение
     * 
     * @param string $content Текст системного промпта
     * @return self
     */
    public function setSystemPrompt(string $content): self
    {
        $this->requestBuilder->setSystemPrompt($content);
        return $this;
    }

    /**
     * Добавляет сообщение ассистента
     * 
     * @param string $content Текст ответа ассистента
     * @return self
     */
    public function setAssistantPrompt(string $content): self
    {
        $this->requestBuilder->setAssistantPrompt($content);
        return $this;
    }

    /**
     * Добавляет объект Message в коллекцию
     * 
     * @param Message $message Объект сообщения
     * @return self
     */
    public function addMessage(Message $message): self
    {
        $this->requestBuilder->addMessage($message);
        return $this;
    }

    // ========== Делегирование методов RequestBuilder для опций ==========

    /**
     * Устанавливает название модели AI
     * 
     * @param string $model Название модели
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->requestBuilder->setModel($model);
        return $this;
    }

    /**
     * Устанавливает температуру
     * 
     * @param float|null $temperature Температура (0.0-2.0)
     * @return self
     */
    public function setTemperature(?float $temperature): self
    {
        $this->requestBuilder->setTemperature($temperature);
        return $this;
    }

    /**
     * Устанавливает максимальное количество токенов
     * 
     * @param int|null $maxTokens Максимальное количество токенов
     * @return self
     */
    public function setMaxTokens(?int $maxTokens): self
    {
        $this->requestBuilder->setMaxTokens($maxTokens);
        return $this;
    }

    /**
     * Устанавливает штраф за частоту
     * 
     * @param float|null $frequencyPenalty Штраф за частоту (-2.0-2.0)
     * @return self
     */
    public function setFrequencyPenalty(?float $frequencyPenalty): self
    {
        $this->requestBuilder->setFrequencyPenalty($frequencyPenalty);
        return $this;
    }

    /**
     * Устанавливает штраф за присутствие
     * 
     * @param float|null $presencePenalty Штраф за присутствие (-2.0-2.0)
     * @return self
     */
    public function setPresencePenalty(?float $presencePenalty): self
    {
        $this->requestBuilder->setPresencePenalty($presencePenalty);
        return $this;
    }

    /**
     * Устанавливает флаг запоминания контекста
     * 
     * @param bool|null $rememberContext Запоминать контекст
     * @return self
     */
    public function setRememberContext(?bool $rememberContext): self
    {
        $this->requestBuilder->setRememberContext($rememberContext);
        return $this;
    }

    /**
     * Устанавливает режим "мышления"
     * 
     * @param bool|null $isThinking Режим мышления
     * @return self
     */
    public function setIsThinking(?bool $isThinking): self
    {
        $this->requestBuilder->setIsThinking($isThinking);
        return $this;
    }

    /**
     * Устанавливает формат ответа
     * 
     * @param ResponseFormat|null $responseFormat Формат ответа
     * @return self
     */
    public function setResponseFormat(?ResponseFormat $responseFormat): self
    {
        $this->requestBuilder->setResponseFormat($responseFormat);
        return $this;
    }

    /**
     * Устанавливает массив строк для остановки генерации
     * 
     * @param array<string>|null $stop Массив строк для остановки
     * @return self
     */
    public function setStop(?array $stop): self
    {
        $this->requestBuilder->setStop($stop);
        return $this;
    }

    /**
     * Устанавливает флаг streaming
     * 
     * @param bool|null $stream Включить потоковую передачу
     * @return self
     */
    public function setStream(?bool $stream): self
    {
        $this->requestBuilder->setStream($stream);
        return $this;
    }

    /**
     * Устанавливает опции для streaming
     * 
     * @param array|null $streamOptions Опции для streaming
     * @return self
     */
    public function setStreamOptions(?array $streamOptions): self
    {
        $this->requestBuilder->setStreamOptions($streamOptions);
        return $this;
    }

    /**
     * Устанавливает Top-p
     * 
     * @param float|null $topP Top-p (0.0-1.0)
     * @return self
     */
    public function setTopP(?float $topP): self
    {
        $this->requestBuilder->setTopP($topP);
        return $this;
    }

    /**
     * Устанавливает инструменты для function calling
     * 
     * @param array|null $tools Массив определений функций
     * @return self
     */
    public function setTools(?array $tools): self
    {
        $this->requestBuilder->setTools($tools);
        return $this;
    }

    /**
     * Устанавливает выбор инструментов
     * 
     * @param array|string|null $toolChoice Выбор инструментов
     * @return self
     */
    public function setToolChoice(array|string|null $toolChoice): self
    {
        $this->requestBuilder->setToolChoice($toolChoice);
        return $this;
    }

    /**
     * Устанавливает флаг логирования вероятностей
     * 
     * @param bool|null $logprobs Включить логи вероятностей
     * @return self
     */
    public function setLogprobs(?bool $logprobs): self
    {
        $this->requestBuilder->setLogprobs($logprobs);
        return $this;
    }

    /**
     * Устанавливает количество топ логов вероятностей
     * 
     * @param int|null $topLogprobs Количество топ логов (0-20)
     * @return self
     */
    public function setTopLogprobs(?int $topLogprobs): self
    {
        $this->requestBuilder->setTopLogprobs($topLogprobs);
        return $this;
    }

    /**
     * Алиас для метода send()
     * 
     * Отправляет запрос через провайдер и создает Turn с ответом
     * 
     * @return Turn
     * 
     * @throws \InvalidArgumentException Если класс Turn не существует или не может быть создан
     * @throws \InvalidArgumentException Если Request не может быть создан (пустые сообщения или отсутствует model)
     * @throws \RuntimeException Если провайдер не установлен
     * 
     * @example
     * $turn = MyTurn::query()
     *     ->withProvider($provider)
     *     ->setUserPrompt('Привет')
     *     ->setModel('deepseek-chat')
     *     ->build();
     */
    public function build(): Turn
    {
        return $this->send();
    }

    /**
     * Устанавливает провайдер для отправки запросов
     * 
     * @param ProviderInterface $provider Провайдер для работы с AI API
     * @return self
     * 
     * @example
     * $turn = MyTurn::query()
     *     ->withProvider($provider)
     *     ->setUserPrompt('Привет')
     *     ->setModel('deepseek-chat')
     *     ->send();
     */
    public function withProvider(ProviderInterface $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Отправляет запрос через провайдер и создает Turn с ответом
     * 
     * @return Turn
     * 
     * @throws \InvalidArgumentException Если класс Turn не существует или не может быть создан
     * @throws \InvalidArgumentException Если Request не может быть создан (пустые сообщения или отсутствует model)
     * @throws \RuntimeException Если провайдер не установлен
     * 
     * @example
     * $turn = MyTurn::query()
     *     ->withProvider($provider)
     *     ->setUserPrompt('Привет')
     *     ->setModel('deepseek-chat')
     *     ->setTemperature(0.7)
     *     ->send();
     */
    public function send(): Turn
    {
        // 1. Собираем request
        $request = $this->requestBuilder->build();

        // 2. Проверяем наличие провайдера
        if ($this->provider === null) {
            throw new \RuntimeException(
                'Provider is not set. Use withProvider() method to set provider before calling send().'
            );
        }

        // 3. Отправляем запрос через провайдер
        $response = $this->provider->sendChatTurn($request);

        // 4. Проверяем класс Turn
        if (!class_exists($this->turnClass)) {
            throw new \InvalidArgumentException("Class {$this->turnClass} does not exist");
        }

        // Проверяем, что класс является либо самим Turn, либо его наследником
        if ($this->turnClass !== Turn::class && !is_subclass_of($this->turnClass, Turn::class)) {
            throw new \InvalidArgumentException("Class {$this->turnClass} must be " . Turn::class . " or extend it");
        }

        // 5. Создаем и возвращаем Turn с запросом и ответом
        return new $this->turnClass($request, $response);
    }

    /**
     * Магический метод для вызова scope методов из класса Turn
     * 
     * Позволяет вызывать методы вида scopeMethodName() из класса Turn
     * как обычные методы билдера.
     * 
     * @param string $method Имя метода
     * @param array $parameters Параметры метода
     * @return self
     * 
     * @throws \BadMethodCallException Если scope метод не существует
     * 
     * @example
     * // В классе MyTurn определен метод:
     * // protected static function scopeMathSolver(TurnBuilder $builder): void { ... }
     * 
     * // Можно вызвать:
     * MyTurn::query()->mathSolver();
     */
    public function __call(string $method, array $parameters)
    {
        // Формируем имя метода скоупа
        $scopeMethod = 'scope' . ucfirst($method);

        // Проверяем, есть ли такой метод в классе Turn
        if (method_exists($this->turnClass, $scopeMethod)) {
            // Вызываем метод скоупа, передавая ему этот билдер
            $this->turnClass::$scopeMethod($this, ...$parameters);
            return $this;
        }

        throw new \BadMethodCallException("Scope {$method} does not exist in " . $this->turnClass);
    }
}
