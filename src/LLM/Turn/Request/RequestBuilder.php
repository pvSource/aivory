<?php

namespace PvSource\Aivory\LLM\Turn\Request;

use PvSource\Aivory\LLM\Turn\Message\Message;
use PvSource\Aivory\LLM\Turn\Message\MessageCollection;
use PvSource\Aivory\LLM\Turn\Options\TurnOptionsBuilder;

/**
 * Builder для создания Request
 * 
 * Позволяет пошагово настраивать параметры Request через fluent API.
 * Делегирует настройку опций в TurnOptionsBuilder.
 * 
 * @example
 * $request = (new RequestBuilder())
 *     ->setPrompt(Message::ROLE_USER, 'Привет')
 *     ->setModel('deepseek-chat')
 *     ->setTemperature(0.7)
 *     ->build();
 */
class RequestBuilder
{
    private MessageCollection $messages;
    private TurnOptionsBuilder $optionsBuilder;

    public function __construct()
    {
        $this->messages = new MessageCollection();
        $this->optionsBuilder = new TurnOptionsBuilder();
    }

    /**
     * Добавляет сообщение в коллекцию
     * 
     * @param string $role Роль сообщения (Message::ROLE_USER, Message::ROLE_SYSTEM, Message::ROLE_ASSISTANT)
     * @param string $prompt Текст сообщения
     * @return self
     * 
     * @example
     * $builder->setPrompt(Message::ROLE_USER, 'Привет');
     */
    public function setPrompt(string $role, string $prompt): self
    {
        $this->messages->push(new Message(
            role: $role,
            content: $prompt
        ));
        return $this;
    }

    /**
     * Добавляет сообщение пользователя
     * 
     * @param string $content Текст сообщения
     * @return self
     * 
     * @example
     * $builder->setUserPrompt('Привет');
     */
    public function setUserPrompt(string $content): self
    {
        return $this->setPrompt(Message::ROLE_USER, $content);
    }

    /**
     * Добавляет системное сообщение
     * 
     * @param string $content Текст системного промпта
     * @return self
     * 
     * @example
     * $builder->setSystemPrompt('Ты помощник по математике');
     */
    public function setSystemPrompt(string $content): self
    {
        return $this->setPrompt(Message::ROLE_SYSTEM, $content);
    }

    /**
     * Добавляет сообщение ассистента
     * 
     * @param string $content Текст ответа ассистента
     * @return self
     * 
     * @example
     * $builder->setAssistantPrompt('Здравствуйте!');
     */
    public function setAssistantPrompt(string $content): self
    {
        return $this->setPrompt(Message::ROLE_ASSISTANT, $content);
    }

    /**
     * Добавляет объект Message в коллекцию
     * 
     * @param Message $message Объект сообщения
     * @return self
     * 
     * @example
     * $builder->addMessage(Message::user('Привет'));
     */
    public function addMessage(Message $message): self
    {
        $this->messages->push($message);
        return $this;
    }

    /**
     * Возвращает коллекцию сообщений
     * 
     * @return MessageCollection
     */
    public function getMessages(): MessageCollection
    {
        return $this->messages;
    }

    /**
     * Возвращает билдер опций
     * 
     * @return TurnOptionsBuilder
     */
    public function getOptionsBuilder(): TurnOptionsBuilder
    {
        return $this->optionsBuilder;
    }

    // ========== Делегирование методов TurnOptionsBuilder ==========

    /**
     * Устанавливает название модели AI
     * 
     * @param string $model Название модели
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->optionsBuilder->setModel($model);
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
        $this->optionsBuilder->setTemperature($temperature);
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
        $this->optionsBuilder->setMaxTokens($maxTokens);
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
        $this->optionsBuilder->setFrequencyPenalty($frequencyPenalty);
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
        $this->optionsBuilder->setPresencePenalty($presencePenalty);
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
        $this->optionsBuilder->setRememberContext($rememberContext);
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
        $this->optionsBuilder->setIsThinking($isThinking);
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
        $this->optionsBuilder->setResponseFormat($responseFormat);
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
        $this->optionsBuilder->setStop($stop);
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
        $this->optionsBuilder->setStream($stream);
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
        $this->optionsBuilder->setStreamOptions($streamOptions);
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
        $this->optionsBuilder->setTopP($topP);
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
        $this->optionsBuilder->setTools($tools);
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
        $this->optionsBuilder->setToolChoice($toolChoice);
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
        $this->optionsBuilder->setLogprobs($logprobs);
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
        $this->optionsBuilder->setTopLogprobs($topLogprobs);
        return $this;
    }

    /**
     * Создает объект Request на основе установленных значений
     * 
     * @return Request
     * 
     * @throws \InvalidArgumentException Если model не установлен или коллекция сообщений пуста
     * 
     * @example
     * $request = $builder
     *     ->setUserPrompt('Привет')
     *     ->setModel('deepseek-chat')
     *     ->setTemperature(0.7)
     *     ->build();
     */
    public function build(): Request
    {
        if ($this->messages->isEmpty()) {
            throw new \InvalidArgumentException('Messages collection cannot be empty. Use setPrompt() or addMessage() to add messages.');
        }

        $turnOptions = $this->optionsBuilder->build();

        return new Request(
            messages: $this->messages,
            turnOptions: $turnOptions
        );
    }
}
