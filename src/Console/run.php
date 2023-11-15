<?php

use Src\MQ\Worker\MainWorker;

if (php_sapi_name() != 'cli') {
    die();
}

require __DIR__ . '/../../vendor/autoload.php';

$messageManager = \Src\DI\Container::getInstance()->get(\Src\MQ\MessageManager::class);

try {
    $messageManager->connect();
    $connection = $messageManager->getConnection();
} catch (Throwable $e) {
    return;
}

$worker = new MainWorker($connection, \Src\MQ\Queue::TRANSCRIBE_IN);
$worker->run();
