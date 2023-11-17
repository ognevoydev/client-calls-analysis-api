<?php

use Src\MQ\Worker\MainWorker;
use Src\Tools\Console;

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
    Console::write('Не удалось установить соединение', Console::COLOR_RED);
    return;
}

if (!isset($argv[1])) {
    Console::write('Не указан идентификатор очереди', Console::COLOR_RED);
    return;
}

$queueId = $argv[1];

// TODO - убрать обращение к контейнеру
$worker = $container->make(MainWorker::class, [
    'AMQPConnection' => $connection,
    'queueId' => $queueId
]);

Console::write('Работник для очереди ' . $queueId . ' создан', Console::COLOR_GREEN);

$worker->run();
