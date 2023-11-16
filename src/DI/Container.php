<?php

namespace Src\DI;

use DI\ContainerBuilder;
use Src\MQ\MessageManager;
use Src\Service\ObjectStorageService;
use Src\Service\OpenAIService;
use Src\Service\TranscriptionService;

class Container
{
    protected static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = self::build();
        }

        return self::$instance;
    }

    private static function build()
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);

        $containerBuilder->addDefinitions(self::getDependencies());

        return $containerBuilder->build();
    }

    private static function getDependencies()
    {
        return [
            MessageManager::class => function (\DI\Container $c) {
                return new MessageManager([
                    'host' => 'rabbitmq',
                    'port' => 5672,
                    'user' => 'rmuser',
                    'password' => 'rmpassword',
                ]);
            },
            ObjectStorageService::class => function (\DI\Container $c) {
                return new ObjectStorageService([
                    'version' => 'latest',
                    'region' => 'ru-central1',
                    'endpoint' => 'https://storage.yandexcloud.net',
                    'credentials' => [
                        'key' => '',
                        'secret' => '',
                    ],
                ]);
            },
            TranscriptionService::class => function (\DI\Container $c) {
                return new TranscriptionService([
                    'url' => 'https://transcribe.api.cloud.yandex.net/speech/stt/v2/longRunningRecognize',
                    'headers' => [
                        'Authorization' => 'Api-Key ',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'data' => [
                        'config' => [
                            'specification' => [
                                'languageCode' => 'ru-RU',
                                'model' => 'general',
                                'profanityFilter' => false,
                                'literature_text' => true,
                                'audioEncoding' => 'MP3',
                                'sampleRateHertz' => 48000,
                                'audioChannelCount' => 2,
                                'rawResults' => true,
                            ]
                        ],
                    ],
                ]);
            },
            OpenAIService::class => function (\DI\Container $c) {
                return new OpenAIService([
                    'url' => 'https://api.openai.com/v1/chat/completions',
                    'apiKey' => '',
                    'headers' => [
                        'Authorization' => 'Bearer ',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'data' => [
                        'model' => 'gpt-3.5-turbo',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => 'Привет'
                            ]
                        ],
                        'temperature' => 0.7,
                    ],
                ]);
            }
        ];
    }
}