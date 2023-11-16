<?php

namespace Src\Model;

class OperationResult
{
    private array $body;
    private string $queueId;
    private bool $requeue;
    private bool $sendMessage;
    public function __construct(array $body, string $queueId, bool $requeue = false, bool $sendMessage = true)
    {
        $this->body = $body;
        $this->queueId = $queueId;
        $this->requeue = $requeue;
        $this->sendMessage = $sendMessage;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getQueueId(): string
    {
        return $this->queueId;
    }

    public function isRequeue(): bool
    {
        return $this->requeue;
    }

    public function isSendMessage(): bool
    {
        return $this->sendMessage;
    }
}