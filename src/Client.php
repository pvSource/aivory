<?php

namespace PvSource\Aivory;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use PvSource\Aivory\LLM\ProviderInterface;
use PvSource\Aivory\Providers\ProviderFactory;
use PvSource\Aivory\Transport\GuzzleHttpClient;
use PvSource\Aivory\Transport\HttpClientInterface;

/**
 * Главная точка входа в библиотеку
 *
 * Создаёт и координирует работу всех компонентов;
 * - HTTP клиент (по умолчанию Guzzle, но можно передать свой, соответствующий стандартам Psr\Http\Message
 * - Провайдер (DeepSeek, OpenAI и т.д.)
 *
 * @package PvSource\Aivory
 *
 * @example
 * * // HTTP-клиент по умолчанию, провайдер: deepseek
 * * $client = new Client(
 * *     providerName: 'deepseek',
 * *     auth: ['apiKey' => 'sk-...']
 * * );
 * *
 * * @example
 * * // С кастомным HTTP клиентом (например, с прокси)
 * * $guzzle = new GuzzleClient(['proxy' => 'tcp://proxy:8080']);
 * * $http = new GuzzleHttpClient($guzzle);
 * *
 * * $client = new Client(
 * *     providerName: 'deepseek',
 * *     auth: ['apiKey' => 'sk-...'],
 * *     httpClient: $http
 * * );
 */
class Client
{
    /**
     * HTTP клиент
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;

    /**
     * Провайдер для работы с AI API
     * @var ProviderInterface
     */
    private ProviderInterface $provider;

    /**
     * Создает новый Client.
     *
     * @param string $providerName Название провайдера ('deepseek', 'openai' и т.д.)
     * @param array $auth Данные аутентификации, может отличаться для разный провайдеров, пример для Deepseek: ['apiKey' => 'API-ключ']
     * @param HttpClientInterface|null $httpClient Кастомный HTTP клиент (опционально)
     *
     * @throws Exception
     *
     * @example
     * $client = new Client('deepseek', ['apiKey' => 'sk-...']);
     */
    public function __construct(
        string $providerName,
        array $auth = [],
        ?HttpClientInterface $httpClient = null
    )
    {
        if ($httpClient === null) {
            $this->httpClient = new GuzzleHttpClient(
                new GuzzleClient([
                    'timeout' => 600,
                ])
            );
        } else {
            $this->httpClient = $httpClient;
        }

        // Создаем провайдер через фабрику
        $this->provider = ProviderFactory::create(
            providerName: $providerName,
            httpClient: $this->httpClient,
            auth: $auth
        );

    }

    /**
     * Возвращает провайдер для работы с AI API.
     *
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }

    /**
     * Возвращает HTTP клиент
     *
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }
}