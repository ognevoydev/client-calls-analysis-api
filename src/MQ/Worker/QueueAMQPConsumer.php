<?php

namespace Src\MQ\Worker;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Model\OperationResult;

abstract class QueueAMQPConsumer
{
    protected AMQPStreamConnection $connection;

    protected bool $debug = false;

    protected string $queueId;

    protected ?string $exchange = null;

    public function __construct(AMQPStreamConnection $AMQPConnection, string $queueId, ?string $exchange = null)
    {
        $this->connection = $AMQPConnection;
        $this->queueId = $queueId;
        $this->exchange = $exchange;
    }

    public function run($debug = false, $durable = true)
    {
        $this->debug = $debug;
        $channel = $this->connection->channel();

        if ($this->exchange !== null) {
            $channel->exchange_declare($this->exchange, "topic", false, $durable, false);
        }

        try {
            $channel->queue_declare($this->queueId, false, $durable, false, false);
        } catch (AMQPProtocolChannelException $e) {
            if ($e->getCode() == 406) {
                $this->run($debug, !$durable);
                return;
            }
        }

        if ($this->exchange !== null) {
            $channel->queue_bind($this->queueId, $this->exchange);
        }

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($this->queueId, '', false, false, false, false, [$this, 'callback']);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $this->connection->close();
    }

    final function callback(AMQPMessage $message)
    {
        $result = $this->process($message);

        if ($result->isRequeue()) {
            $message->nack(true);
        } else {
            $message->ack();
        }
    }

    /**
     * @param AMQPMessage $message
     *
     * @return bool
     */
    abstract protected function process(AMQPMessage $message): OperationResult;
}
