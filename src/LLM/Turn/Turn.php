<?php

namespace PvSource\Aivory\LLM\Turn;

use PvSource\Aivory\LLM\Turn\Request\Request;
use PvSource\Aivory\LLM\Turn\Request\ResponseFormat;
use PvSource\Aivory\LLM\Turn\Response\ResponseInterface;

/**
 * Базовый класс Turn
 * 
 * Предоставляет возможность переопределения default значений
 * через protected static свойства (как в Eloquent).
 * 
 * @example
 * class MathTurn extends Turn
 * {
 *     protected static ?string $defaultSystemPrompt = 'Ты математик';
 *     protected static ?string $defaultModel = 'deepseek-chat';
 *     protected static ?float $defaultTemperature = 0.3;
 * }
 */
class Turn
{
    private Request $turnRequest;
    private ResponseInterface $turnResponse;

    // ========== Default значения (можно переопределять в наследниках) ==========

    /**
     * Системный промпт по умолчанию
     * 
     * @var string|null
     */
    protected static ?string $defaultSystemPrompt = null;

    /**
     * Модель по умолчанию
     * 
     * @var string|null
     */
    protected static ?string $defaultModel = null;

    /**
     * Температура по умолчанию
     * 
     * @var float|null
     */
    protected static ?float $defaultTemperature = null;

    /**
     * Максимальное количество токенов по умолчанию
     * 
     * @var int|null
     */
    protected static ?int $defaultMaxTokens = null;

    /**
     * Frequency penalty по умолчанию
     * 
     * @var float|null
     */
    protected static ?float $defaultFrequencyPenalty = null;

    /**
     * Presence penalty по умолчанию
     * 
     * @var float|null
     */
    protected static ?float $defaultPresencePenalty = null;

    /**
     * Remember context по умолчанию
     * 
     * @var bool|null
     */
    protected static ?bool $defaultRememberContext = null;

    /**
     * Thinking режим по умолчанию
     * 
     * @var bool|null
     */
    protected static ?bool $defaultIsThinking = null;

    /**
     * JSON Schema для ResponseFormat по умолчанию
     * 
     * @var array|null
     */
    protected static ?array $defaultResponseJsonSchema = null;

    /**
     * Название схемы ResponseFormat по умолчанию
     * 
     * @var string|null
     */
    protected static ?string $defaultResponseFormatName = null;

    /**
     * Stop последовательности по умолчанию
     * 
     * @var array|null
     */
    protected static ?array $defaultStop = null;

    /**
     * Stream режим по умолчанию
     * 
     * @var bool|null
     */
    protected static ?bool $defaultStream = null;

    /**
     * Stream options по умолчанию
     * 
     * @var array|null
     */
    protected static ?array $defaultStreamOptions = null;

    /**
     * Top P по умолчанию
     * 
     * @var float|null
     */
    protected static ?float $defaultTopP = null;

    /**
     * Tools по умолчанию
     * 
     * @var array|null
     */
    protected static ?array $defaultTools = null;

    /**
     * Tool choice по умолчанию
     * 
     * @var array|string|null
     */
    protected static array|string|null $defaultToolChoice = null;

    /**
     * Logprobs по умолчанию
     * 
     * @var bool|null
     */
    protected static ?bool $defaultLogprobs = null;

    /**
     * Top logprobs по умолчанию
     * 
     * @var int|null
     */
    protected static ?int $defaultTopLogprobs = null;

    // ========== Конструктор и основные методы ==========

    public function __construct(Request $request, ResponseInterface $response)
    {
        $this->turnRequest = $request;
        $this->turnResponse = $response;
    }

    // Статический метод для создания билдера
    public static function query(): TurnBuilder
    {
        return new TurnBuilder(static::class);
    }

    // Алиас для query() (как в некоторых ORM)
    public static function builder(): TurnBuilder
    {
        return static::query();
    }

    public function getRequest(): Request
    {
        return $this->turnRequest;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->turnResponse;
    }

    // ========== Публичные статические методы для получения default значений ==========

    /**
     * Возвращает системный промпт по умолчанию
     * 
     * @return string|null
     */
    public static function getDefaultSystemPrompt(): ?string
    {
        return static::$defaultSystemPrompt;
    }

    /**
     * Возвращает модель по умолчанию
     * 
     * @return string|null
     */
    public static function getDefaultModel(): ?string
    {
        return static::$defaultModel;
    }

    /**
     * Возвращает температуру по умолчанию
     * 
     * @return float|null
     */
    public static function getDefaultTemperature(): ?float
    {
        return static::$defaultTemperature;
    }

    /**
     * Возвращает максимальное количество токенов по умолчанию
     * 
     * @return int|null
     */
    public static function getDefaultMaxTokens(): ?int
    {
        return static::$defaultMaxTokens;
    }

    /**
     * Возвращает frequency penalty по умолчанию
     * 
     * @return float|null
     */
    public static function getDefaultFrequencyPenalty(): ?float
    {
        return static::$defaultFrequencyPenalty;
    }

    /**
     * Возвращает presence penalty по умолчанию
     * 
     * @return float|null
     */
    public static function getDefaultPresencePenalty(): ?float
    {
        return static::$defaultPresencePenalty;
    }

    /**
     * Возвращает remember context по умолчанию
     * 
     * @return bool|null
     */
    public static function getDefaultRememberContext(): ?bool
    {
        return static::$defaultRememberContext;
    }

    /**
     * Возвращает thinking режим по умолчанию
     * 
     * @return bool|null
     */
    public static function getDefaultIsThinking(): ?bool
    {
        return static::$defaultIsThinking;
    }

    /**
     * Возвращает ResponseFormat по умолчанию
     * 
     * Если установлен defaultResponseJsonSchema, создает ResponseFormat из него.
     * 
     * @return ResponseFormat|null
     */
    public static function getDefaultResponseFormat(): ?ResponseFormat
    {
        if (static::$defaultResponseJsonSchema) {
            return ResponseFormat::jsonSchema(
                schema: static::$defaultResponseJsonSchema,
                name: static::$defaultResponseFormatName,
            );
        }

        return null;
    }

    /**
     * Возвращает название схемы ResponseFormat по умолчанию
     * 
     * @return string|null
     */
    public static function getDefaultResponseFormatName(): ?string
    {
        return static::$defaultResponseFormatName;
    }

    /**
     * Возвращает stop последовательности по умолчанию
     * 
     * @return array|null
     */
    public static function getDefaultStop(): ?array
    {
        return static::$defaultStop;
    }

    /**
     * Возвращает stream режим по умолчанию
     * 
     * @return bool|null
     */
    public static function getDefaultStream(): ?bool
    {
        return static::$defaultStream;
    }

    /**
     * Возвращает stream options по умолчанию
     * 
     * @return array|null
     */
    public static function getDefaultStreamOptions(): ?array
    {
        return static::$defaultStreamOptions;
    }

    /**
     * Возвращает top P по умолчанию
     * 
     * @return float|null
     */
    public static function getDefaultTopP(): ?float
    {
        return static::$defaultTopP;
    }

    /**
     * Возвращает tools по умолчанию
     * 
     * @return array|null
     */
    public static function getDefaultTools(): ?array
    {
        return static::$defaultTools;
    }

    /**
     * Возвращает tool choice по умолчанию
     * 
     * @return array|string|null
     */
    public static function getDefaultToolChoice(): array|string|null
    {
        return static::$defaultToolChoice;
    }

    /**
     * Возвращает logprobs по умолчанию
     * 
     * @return bool|null
     */
    public static function getDefaultLogprobs(): ?bool
    {
        return static::$defaultLogprobs;
    }

    /**
     * Возвращает top logprobs по умолчанию
     * 
     * @return int|null
     */
    public static function getDefaultTopLogprobs(): ?int
    {
        return static::$defaultTopLogprobs;
    }
}