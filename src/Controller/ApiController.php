<?php

namespace Src\Controller;

use Src\Service\ObjectStorageService;
use Src\Service\OpenAIService;
use Src\Service\TranscriptionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApiController
{

    protected ObjectStorageService $objectStorageService;
    protected TranscriptionService $transcriptionService;
    protected OpenAIService $openAIService;

    public function __construct(ObjectStorageService $objectStorageService,
                                TranscriptionService $transcriptionService,
                                OpenAIService $openAIService)
    {
        $this->objectStorageService = $objectStorageService;
        $this->transcriptionService = $transcriptionService;
        $this->openAIService = $openAIService;
    }

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
                $this->objectStorageService->upload($path, 'mybucket191');

                // Транскрибация записи
                $this->transcriptionService->transcribe('https://storage.yandexcloud.net/' . 'mybucket191' . '/' . 'short_sample.mp3');

                // Отправка промпта в нейросеть
                $openAIResponse = $this->openAIService->sendRequest();
            }
        }

        $data = [
            'status' => 'success',
            'response' => $openAIResponse
        ];
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
}