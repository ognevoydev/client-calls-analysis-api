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
        $fileName = $body['fileName'];
        $bucket = $body['bucket'];

        $operationId = $this->serviceFactory->getTranscriptionService()->transcribe($fileName, $bucket);

        $result = [
            'operationId' => $operationId,
        ];

        return new OperationResult($result, Queue::TRANSCRIBE_PROCESS);
    }

    public function getTranscriptionStatus($body)
    {
        $operationId = $body['operationId'];

        $sleepTime = 5; // 1 мин 1 канал - 10 сек

        $finalText = $this->serviceFactory->getTranscriptionService()->getText($operationId);

        $result = [];

        $requeue = false;
        $sendMessage = true;

        if (empty($finalText)) {
            $result['operationId'] = $body['operationId'];
            $queueId = Queue::TRANSCRIBE_PROCESS;
            $requeue = true;
            $sendMessage = false;
        } else {
            $queueId = Queue::GENERATE_PROMPT;
            $result['text'] = $finalText;
        }

        sleep($sleepTime);

        return new OperationResult($result, $queueId, $requeue, $sendMessage);
    }

    public function generatePrompts($body)
    {
        $text = $body['text'];
        $prompts = $this->serviceFactory->getPromptGenerationService()->generate($text);

        $result = [
            'prompts' => $prompts,
        ];

        return new OperationResult($result, Queue::SEND_REQUEST);
    }

    public function sendRequest($body)
    {
        $prompts = $body['prompts'];
        $result = $this->serviceFactory->getOpenAIService()->sendRequest($prompts);

        return new OperationResult([], '', false, false);
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
            Queue::GENERATE_PROMPT => function ($body) {
                return $this->generatePrompts($body);
            },
            Queue::SEND_REQUEST => function ($body) {
                return $this->sendRequest($body);
            },
        ];
    }
}