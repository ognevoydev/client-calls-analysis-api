<?php

namespace Src\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\MQ\MessageManager;
use Src\MQ\Queue;

class ApiController
{

    protected MessageManager $messageManager;

    public function __construct(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
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

                $this->messageManager->connect();
                $this->messageManager->send($path, Queue::TRANSCRIBE_IN);
            }
        }

        $data = [
            'status' => 'success',
            'response' => ''
        ];
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
}