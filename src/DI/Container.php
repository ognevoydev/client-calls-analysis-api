<?php

namespace Src\DI;

use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Setup;
use Src\MQ\MessageManager;
use Src\Service\ObjectStorageService;
use Src\Service\OpenAIService;
use Src\Service\PromptGenerationService;
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
            EntityManager::class => function (\DI\Container $c) {

                $config = ORMSetup::createAttributeMetadataConfiguration(
                    paths: [__DIR__ . "/../Entity"],
                );

                $connection = DriverManager::getConnection([
                    'driver' => 'pdo_mysql',
                    'host' => $_ENV['DB_HOST'],
                    'port' => $_ENV['DB_PORT'],
                    'dbname' => $_ENV['DB_NAME'],
                    'user' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASSWORD'],
                ], $config);

                return new EntityManager($connection, $config);
            },
            MessageManager::class => function (\DI\Container $c) {
                return new MessageManager([
                    'host' => $_ENV['MQ_HOST'],
                    'port' => $_ENV['MQ_PORT'],
                    'user' => $_ENV['MQ_USER'],
                    'password' => $_ENV['MQ_PASSWORD'],
                ]);
            },
            ObjectStorageService::class => function (\DI\Container $c) {
                return new ObjectStorageService([
                    'version' => 'latest',
                    'region' => 'ru-central1',
                    'endpoint' => 'https://storage.yandexcloud.net',
                    'credentials' => [
                        'key' => $_ENV['OBJECT_STORAGE_API_KEY'],
                        'secret' => $_ENV['OBJECT_STORAGE_SECRET'],
                    ],
                ]);
            },
            TranscriptionService::class => function (\DI\Container $c) {
                return new TranscriptionService([
                    'url' => 'https://transcribe.api.cloud.yandex.net/speech/stt/v2/longRunningRecognize',
                    'headers' => [
                        'Authorization' => 'Api-Key ' . $_ENV['YANDEX_SPEECHKIT_API_KEY'],
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
            PromptGenerationService::class => function (\DI\Container $c) {
                return new PromptGenerationService();
            },
            OpenAIService::class => function (\DI\Container $c) {
                return new OpenAIService([
                    'url' => 'https://api.openai.com/v1/chat/completions',
                    'apiKey' => '',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $_ENV['OPENAI_API_KEY'],
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'data' => [
                        'model' => 'gpt-3.5-turbo',
                        'temperature' => 0.7,
                    ],
                ]);
            }
        ];
    }
}