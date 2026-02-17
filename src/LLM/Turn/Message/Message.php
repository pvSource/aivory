<?php

namespace PvSource\Aivory\LLM\Turn\Message;

use InvalidArgumentException;

/**
 * Представляет сообщение в чате с AI.
 *
 * Message - это Value Object, который представляет одно сообщениие в диалоге.
 * @note Не путать с запросом и ответом. Один запрос и ответ может содержать несколько сообщений.
 *
 * @package PvSource\Aivory\Domain
 *
 * @example
 * // Создание системного сообщения
 * $userMessage = Message::system('Ты помощник по математике, помоги пользователю')
 *
 * @example
 * // Создание сообщения пользователя
 * $userMessage = Message::user('Не могу понять как решается пример 13x - 8 = 4')
 *
 * @example
 * // Преобразование в массив для провайдера
 * $messageArray = $message->toArray(); // ['role' => 'user', 'content' => 'Не могу понять как решается пример 13x - 8 = 4']
 */
class Message
{
    /**
     * Роль: сообщение от пользователя
     */
    public const ROLE_USER = 'user';

    /**
     * Роль: системное сообщение
     */
    public const ROLE_SYSTEM = 'system';

    /**
     * Роль: сообщение от ассистента
     */
    public const ROLE_ASSISTANT = 'assistant';

    /**
     * Создает новое сообщение.
     *
     * @param string $role Роль сообщения. Должна быть одной из констант: {@see self::ROLE_USER}, {@see self::ROLE_SYSTEM}, {@see self::ROLE_ASSISTANT}
     * @param string $content Текст сообщения
     * @param string|null $reasoningContent Размышления ассистента (reasoning_content из ответа API, когда включен isThinking)
     *
     * @throws InvalidArgumentException Если переданная роль недопустима
     *
     * @example
     * $message = new Message(Message::ROLE_USER, 'Привет');
     * 
     * @example
     * // Сообщение с размышлениями (из ответа API)
     * $message = new Message(
     *     Message::ROLE_ASSISTANT,
     *     'Ответ',
     *     'Размышления ассистента...'
     * );
     */
    public function __construct(
        public readonly string $role,
        public readonly string $content,
        public readonly ?string $reasoningContent = null,
    )
    {
        if (!in_array(
            needle: $this->role,
            haystack: [self::ROLE_USER, self::ROLE_SYSTEM, self::ROLE_ASSISTANT],
            strict: true)
        ) {
            throw new InvalidArgumentException("Invalid role: $this->role");
        }
    }

    /**
     * Преобразует сообщение в массив для передачи провайдеру.
     * Используется провайдерами для преобразования доменной модели в формат, ожидаемый API провайдера.
     * 
     * Примечание: reasoningContent не включается в массив, так как это поле только для чтения из ответа API.
     *
     * @return array{role: string, content: string} Массив с ключами 'role' и 'content'
     *
     * @example
     * $message = Message::user('Привет');
     * $array = $message->toArray(); // ['role' => 'user', 'content' => 'Привет']
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content
        ];
    }

    /**
     * Создает сообщение от пользователя.
     *
     * @param string $content Текст сообщения пользователя
     *
     * @return self Новый экземпляр Message с ролью {@see self::ROLE_USER}
     *
     * @example
     * $message = Message::user('Как решить уравнение 2x + 5 = 15?');
     */
    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    /**
     * Создает системный промпт.
     *
     * Системный промпт используется для задания инструкций и контекста
     * для AI ассистента. Обычно отправляется один раз в начале диалога.
     *
     * @param string $content Текст системного промпта
     *
     * @return self Новый экземпляр Message с ролью {@see self::ROLE_SYSTEM}
     *
     * @example
     * $systemPrompt = Message::system('Ты помощник по математике. Объясняй решения подробно.');
     */
    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    /**
     * Создает сообщение от ассистента.
     *
     * Обычно используется для сохранения истории диалога или
     * для передачи предыдущих ответов AI в контекст нового запроса.
     *
     * @param string $content Текст ответа ассистента
     * @param string|null $reasoningContent Размышления ассистента (опционально)
     *
     * @return self Новый экземпляр Message с ролью {@see self::ROLE_ASSISTANT}
     *
     * @example
     * $assistantMessage = Message::assistant('Для решения уравнения нужно...');
     * 
     * @example
     * // С размышлениями
     * $assistantMessage = Message::assistant(
     *     'Ответ',
     *     'Размышления ассистента...'
     * );
     */
    public static function assistant(string $content, ?string $reasoningContent = null): self
    {
        return new self('assistant', $content, $reasoningContent);
    }
}