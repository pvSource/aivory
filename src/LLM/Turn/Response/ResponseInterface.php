<?php

namespace PvSource\Aivory\LLM\Turn\Response;

use PvSource\Aivory\LLM\Turn\Message\Message;
use PvSource\Aivory\LLM\Turn\Message\MessageCollection;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;

interface ResponseInterface
{
    public function getMessages(): MessageCollection;
    
    /**
     * Возвращает сырое тело ответа
     * 
     * @return mixed Обычно array или string
     */
    public function getRawBody(): mixed;
    
    /**
     * Возвращает HTTP заголовки ответа
     * 
     * @return array<string, string[]> Массив заголовков
     */
    public function getHeaders(): array;

    /**
     * Возвращает сообщение от ассистента
     * 
     * Ищет первое сообщение с ролью 'assistant' в коллекции сообщений.
     * 
     * @return Message Сообщение от ассистента
     * 
     * @throws \RuntimeException Если сообщение от ассистента не найдено
     */
    public function getAssistant(): Message;

    /**
     * Возвращает объекты, созданные из ответа в соответствии со схемой
     * 
     * Работает только для ResponseFormat с типом TYPE_JSON_SCHEMA.
     * Парсит JSON из сообщения ассистента и создает объекты через JsonSchemaInterface::fromResponse().
     * 
     * Использует lazy loading - объекты создаются при первом вызове и сохраняются в поле.
     * 
     * @param ResponseFormat|null $responseFormat Схема ответа (опционально, если не передана, будет получена из Request)
     * 
     * @return object Объект, созданный из ответа в соответствии со схемой
     * 
     * @throws \LogicException Если ResponseFormat имеет тип TYPE_TEXT
     * @throws \RuntimeException Если сообщение от ассистента не найдено или не является валидным JSON
     * @throws \InvalidArgumentException Если данные не соответствуют схеме
     */
    public function getSchemaObjects(?ResponseFormat $responseFormat = null): object;
}