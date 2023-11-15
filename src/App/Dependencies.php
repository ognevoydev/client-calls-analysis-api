<?php

use DI\Container;
use DI\ContainerBuilder;
use Src\Service\ObjectStorageService;
use Src\Service\OpenAIService;
use Src\Service\TranscriptionService;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        ObjectStorageService::class => function (Container $c) {
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
        TranscriptionService::class => function (Container $c) {
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
        OpenAIService::class => function (Container $c) {
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
    ]);
};