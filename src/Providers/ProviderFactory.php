<?php

namespace PvSource\Aivory\Providers;

use Exception;
use GuzzleHttp\Psr7\HttpFactory;
use PvSource\Aivory\LLM\ProviderInterface;
use PvSource\Aivory\Providers\DeepSeek\DeepSeekProvider;
use PvSource\Aivory\Providers\DeepSeek\Turn\RequestAdapter as DeepSeekRequestAdapter;
use PvSource\Aivory\Providers\OpenAI\OpenAIProvider;
use PvSource\Aivory\Providers\OpenAI\Turn\RequestAdapter as OpenAIRequestAdapter;
use PvSource\Aivory\Transport\HttpClientInterface;

class ProviderFactory
{
    /**
     * Создает провайдер по имени.
     * 
     * @param string $providerName Название провайдера ('deepseek', 'openai' и т.д.)
     * @param HttpClientInterface $httpClient HTTP клиент для запросов
     * @param array $auth Данные аутентификации ['apiKey' => '...']
     * 
     * @return ProviderInterface
     * 
     * @throws Exception Если провайдер не найден
     */
    public static function create(
        string $providerName,
        HttpClientInterface $httpClient,
        array $auth = []
    ): ProviderInterface {
        $httpFactory = new HttpFactory();
        
        return match ($providerName) {
            'deepseek' => new DeepSeekProvider(
                httpClient: $httpClient,
                requestAdapter: new DeepSeekRequestAdapter(
                    requestFactory: $httpFactory,
                    streamFactory: $httpFactory,
                    uriFactory: $httpFactory,
                ),
                apiKey: $auth['apiKey'] ?? '',
            ),
            'openai' => new OpenAIProvider(
                httpClient: $httpClient,
                requestAdapter: new OpenAIRequestAdapter(
                    requestFactory: $httpFactory,
                    streamFactory: $httpFactory,
                    uriFactory: $httpFactory,
                ),
                apiKey: $auth['apiKey'] ?? '',
            ),
            default => throw new Exception("Неизвестное имя провайдера: $providerName"),
        };
    }
}