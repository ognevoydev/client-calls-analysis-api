<?php

namespace Src\MQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessageManager
{
    protected array $config;

    protected AMQPStreamConnection $connection;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect()
    {
        try {
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['user'],
                $this->config['password'],

            );
        } catch (\Throwable $e) {
            throw new \Exception("Ошибка подключения: {$e->getMessage()}");
        }

        return $this->connection->isConnected();
    }

    public function setConnection(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function send(string $body, string $queueId)
    {
        $channel = $this->connection->channel();
        $channel->queue_declare($queueId, false, true, false, false);

        $properties = [];

        $message = new AMQPMessage($body, $properties);

        $channel->basic_publish($message, '', $queueId);
    }

    public function disconnect()
    {
        try {
            $this->connection->close();
        } catch (\Throwable $e) {
            throw new \Exception("Ошибка разрыва соединения: {$e->getMessage()}");
        }
    }
}