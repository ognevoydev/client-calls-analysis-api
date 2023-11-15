<?php

namespace Src\Controller;

use Src\Service\ObjectStorageService;
use Src\Service\OpenAIService;
use Src\Service\TranscriptionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApiController
{
    public function uploadRecord(Request $request, Response $response, $args): Response
    {
        $token = $request->getHeader('Authorization');

        $checkToken = true; // TODO - проверка токена

        if (!$checkToken) {
            $response->getBody()->write(json_encode(['error' => 'Authentication failed']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $uploadedFiles = $request->getUploadedFiles();

        if (isset($uploadedFiles['record'])) {
            $record = $uploadedFiles['record'];

            if ($record->getError() === UPLOAD_ERR_OK) {
                // Сохранение записи на сервер
                $fileName = $record->getClientFilename();
                $path = '/tmp/' . $fileName;
                $record->moveTo($path);

                // Загрузка записи в облако
                $objectStorageService = new ObjectStorageService([
                    'version' => 'latest',
                    'region' => 'ru-central1',
                    'endpoint' => 'https://storage.yandexcloud.net',
                    'credentials' => [
                        'key' => '',
                        'secret' => '',
                    ],
                ]);
                $objectStorageService->upload($path, 'mybucket191');

                // Транскрибация записи
                $transcriptionService = new TranscriptionService([
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
                $transcriptionService->transcribe('https://storage.yandexcloud.net/' . 'mybucket191' . '/' . 'short_sample.mp3');

                // Отправка промпта в нейросеть
                $openAIService = new OpenAIService([
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
                            'role' => 'user',
                            'content' => 'I want you to do this thing.'
                        ],
                        'temperature' => 0.7,
                    ],
                ]);

                $openAIResponse = $openAIService->sendRequest();
            }
        }

        $data = [
            'status' => 'success',
        ];
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
}