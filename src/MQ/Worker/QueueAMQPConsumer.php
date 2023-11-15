<?php

namespace Src\MQ\Worker;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;

abstract class QueueAMQPConsumer
{
    protected AMQPStreamConnection $connection;

    protected bool $debug = false;

    protected string $queueName;

    protected ?string $exchange = null;

    public function __construct(AMQPStreamConnection $AMQPConnection, string $queueName, ?string $exchange = null)
    {
        $this->connection = $AMQPConnection;
        $this->queueName = $queueName;
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
            $channel->queue_declare($this->queueName, false, $durable, false, false);
        } catch (AMQPProtocolChannelException $e) {
            if ($e->getCode() == 406) {
                $this->run($debug, !$durable);
                return;
            }
        }

        if ($this->exchange !== null) {
            $channel->queue_bind($this->queueName, $this->exchange);
        }

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($this->queueName, '', false, false, false, false, [$this, 'callback']);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $this->connection->close();
    }


    final function callback(AMQPMessage $message)
    {
        if ($this->process($message)) {
            $message->ack();
        }
    }

    /**
     * @param AMQPMessage $message
     *
     * @return bool
     */
    abstract protected function process(AMQPMessage $message);
}
