<?php

namespace Src\MQ\Worker;

use DI\Attribute\Inject;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Model\OperationResult;
use Src\Service\ProcessManager;

class MainWorker extends QueueAMQPConsumer
{

    #[Inject]
    protected ProcessManager $processManager;

    public function __construct(AMQPStreamConnection $AMQPConnection, string $queueId)
    {
        parent::__construct($AMQPConnection, $queueId);
    }

    protected function process(AMQPMessage $message): OperationResult
    {
        $body = json_decode($message->getBody(), true);
        $properties = $message->get_properties();

        $action = $this->processManager->getActionByQueueId($this->queueId);

        return $this->processManager->launch($action, $body);
    }
}