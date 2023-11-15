<?php

namespace Src\MQ\Worker;

use DI\Attribute\Inject;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Factory\ServiceFactory;

class MainWorker extends QueueAMQPConsumer
{

    #[Inject]
    protected ServiceFactory $serviceFactory;

    public function __construct(AMQPStreamConnection $AMQPConnection, string $queueId)
    {
        parent::__construct($AMQPConnection, $queueId);
    }

    protected function process(AMQPMessage $message)
    {
        $body = $message->getBody();
        $properties = $message->get_properties();

        // Загрузка записи в облако
        $this->serviceFactory->getObjectStorageService()->upload($body, 'mybucket191');

        // Транскрибация записи
        $this->serviceFactory->getTranscriptionService()->transcribe('https://storage.yandexcloud.net/' . 'mybucket191' . '/' . 'short_sample.mp3');

        // Отправка промпта в нейросеть
        $openAIResponse = $this->serviceFactory->getOpenAIService()->sendRequest();

        return true;

    }
}