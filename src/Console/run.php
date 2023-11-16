<?php

use Src\MQ\Worker\MainWorker;

if (php_sapi_name() != 'cli') {
    die();
}

require __DIR__ . '/../../vendor/autoload.php';

$container = \Src\DI\Container::getInstance();

// TODO - убрать обращение к контейнеру
$messageManager = $container->get(\Src\MQ\MessageManager::class);

try {
    $messageManager->connect();
    $connection = $messageManager->getConnection();
} catch (Throwable $e) {
    return;
}

$queueId = $argv[1];

// TODO - убрать обращение к контейнеру
$worker = $container->make(MainWorker::class, [
    'AMQPConnection' => $connection,
    'queueId' => $queueId
]);

$worker->run();
