<?php

namespace Src\Service;

use DI\Attribute\Inject;
use Src\Factory\ServiceFactory;
use Src\Model\OperationResult;
use Src\MQ\MessageManager;
use Src\MQ\Queue;

class ProcessManager
{

    #[Inject]
    public ServiceFactory $serviceFactory;

    #[Inject]
    public MessageManager $messageManager;

    public function __construct()
    {

    }

    public function launch(callable $action, array $args)
    {
        $result = $action($args);

        $body = $result->getBody();
        $queueId = $result->getQueueId();
        echo $queueId . ' ';

        $this->changeState();

        if ($result->isSendMessage()) {
            $this->sendMessage($body, $queueId);
        }

        return $result;
    }

    public function changeState()
    {

    }

    public function sendMessage($body, $queueId)
    {
        $this->messageManager->connect();
        $this->messageManager->send(json_encode($body), $queueId);
        $this->messageManager->disconnect();
    }

    public function upload($body)
    {
        $path = $body['path'];
        $bucket = $body['bucket'];
        $result = $this->serviceFactory->getObjectStorageService()->upload($path, $bucket);

        return new OperationResult($result, Queue::TRANSCRIBE_IN);
    }

    public function transcribe($body)
    {
        // $audioUrl = body['audioUrl'];
        $audioUrl = 'https://storage.yandexcloud.net/' . 'mybucket191' . '/' . 'short_sample.mp3';

        $operationId = $this->serviceFactory->getTranscriptionService()->transcribe($audioUrl);

        $result = [
            'operationId' => $operationId,
        ];

        return new OperationResult($result, Queue::TRANSCRIBE_PROCESS);
    }

    public function getTranscriptionStatus($body)
    {
        $operationId = $body['operationId'];

        $sleepTime = 5; // 1 мин 1 канал - 10 сек

        $result = $this->serviceFactory->getTranscriptionService()->getStatus($operationId);

        $requeue = false;
        $sendMessage = true;

        if (empty($result['done'])) {
            $result['operationId'] = $body['operationId'];
            $queueId = Queue::TRANSCRIBE_PROCESS;
            $requeue = true;
            $sendMessage = false;
        } else {
            $queueId = Queue::SEND_REQUEST;
        }

        sleep($sleepTime);

        return new OperationResult($result, $queueId, $requeue, $sendMessage);
    }

    public function sendRequest($body)
    {
        $result = $this->serviceFactory->getOpenAIService()->sendRequest();

        return new OperationResult($result, '', false, false);
    }

    public function getActionByQueueId(string $queueId)
    {
        return $this->getActions()[$queueId];
    }

    public function getActions()
    {
        return [
            Queue::UPLOAD => function ($body) {
                return $this->upload($body);
            },
            Queue::TRANSCRIBE_IN => function ($body) {
                return $this->transcribe($body);
            },
            Queue::TRANSCRIBE_PROCESS => function ($body) {
                return $this->getTranscriptionStatus($body);
            },
            Queue::SEND_REQUEST => function ($body) {
                return $this->sendRequest($body);
            },
        ];
    }
}