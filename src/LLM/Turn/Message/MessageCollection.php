<?php

namespace PvSource\Aivory\LLM\Turn\Message;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;

/**
 * Коллекция сообщений (Message)
 * 
 * Реализует интерфейсы массива (ArrayAccess, Iterator, Countable),
 * позволяя работать с коллекцией как с массивом, но гарантирует,
 * что содержит только объекты Message.
 * 
 * @implements ArrayAccess<int, Message>
 * @implements Iterator<int, Message>
 * 
 * @example
 * $collection = new MessageCollection([
 *     Message::user('Привет'),
 *     Message::assistant('Здравствуйте!')
 * ]);
 * 
 * // Работа как с массивом
 * $collection[] = Message::system('Ты помощник');
 * $message = $collection[0];
 * count($collection);
 * 
 * // Итерация
 * foreach ($collection as $message) {
 *     echo $message->content;
 * }
 * 
 * // Методы Laravel-стиля
 * $collection->push(Message::user('Новое сообщение'));
 * $first = $collection->first();
 * $last = $collection->last();
 */
class MessageCollection implements ArrayAccess, Iterator, Countable
{
    /**
     * @var Message[]
     */
    private array $items = [];
    
    private int $position = 0;

    /**
     * Создает новую коллекцию сообщений
     * 
     * @param Message[] $messages Массив объектов Message
     * 
     * @throws InvalidArgumentException Если передан не объект Message
     */
    public function __construct(array $messages = [])
    {
        foreach ($messages as $message) {
            $this->validateMessage($message);
            $this->items[] = $message;
        }
    }

    /**
     * Преобразует коллекцию в массив массивов
     * 
     * Каждый Message преобразуется в массив через метод toArray()
     * 
     * @return array<int, array{role: string, content: string}>
     * 
     * @example
     * $collection = new MessageCollection([Message::user('Привет')]);
     * $array = $collection->toArray();
     * // [['role' => 'user', 'content' => 'Привет']]
     */
    public function toArray(): array
    {
        return array_map(
            fn(Message $message) => $message->toArray(),
            $this->items
        );
    }

    /**
     * Добавляет сообщение в конец коллекции
     * 
     * @param Message $message Сообщение для добавления
     * @return self
     * 
     * @example
     * $collection->push(Message::user('Новое сообщение'));
     */
    public function push(Message $message): self
    {
        $this->items[] = $message;
        return $this;
    }

    /**
     * Добавляет сообщение в начало коллекции
     * 
     * @param Message $message Сообщение для добавления
     * @return self
     */
    public function prepend(Message $message): self
    {
        array_unshift($this->items, $message);
        return $this;
    }

    /**
     * Возвращает первое сообщение в коллекции
     * 
     * @return Message|null Первое сообщение или null, если коллекция пуста
     */
    public function first(): ?Message
    {
        return $this->items[0] ?? null;
    }

    /**
     * Возвращает последнее сообщение в коллекции
     * 
     * @return Message|null Последнее сообщение или null, если коллекция пуста
     */
    public function last(): ?Message
    {
        if (empty($this->items)) {
            return null;
        }
        return $this->items[count($this->items) - 1];
    }

    /**
     * Проверяет, пуста ли коллекция
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Возвращает все элементы коллекции как массив
     * 
     * @return Message[]
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Удаляет элемент по индексу
     * 
     * @param int $offset Индекс элемента
     * @return void
     */
    public function remove(int $offset): void
    {
        if (isset($this->items[$offset])) {
            unset($this->items[$offset]);
            $this->items = array_values($this->items); // Переиндексация
        }
    }

    /**
     * Очищает коллекцию
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->items = [];
        return $this;
    }

    /**
     * Проверяет, что переданный элемент является объектом Message
     * 
     * @param mixed $message
     * @throws InvalidArgumentException
     */
    private function validateMessage(mixed $message): void
    {
        if (!$message instanceof Message) {
            throw new InvalidArgumentException(
                'MessageCollection can only contain Message objects. Got: ' . get_debug_type($message)
            );
        }
    }

    // ========== ArrayAccess ==========

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): ?Message
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->validateMessage($value);
        
        if ($offset === null) {
            // $collection[] = $message
            $this->items[] = $value;
        } else {
            // $collection[0] = $message
            $this->items[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
        $this->items = array_values($this->items); // Переиндексация
    }

    // ========== Iterator ==========

    /**
     * @inheritDoc
     */
    public function current(): Message
    {
        return $this->items[$this->position];
    }

    /**
     * @inheritDoc
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    // ========== Countable ==========

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }
}
