<?php

/**
 * php src/Console/setup_database.php orm:schema-tool:create
 * for dev: docker inspect neuroanal_mysql | grep IPAddress
 */

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Src\Tools\Console;

if (php_sapi_name() != 'cli') {
    die();
}

require __DIR__ . '/../../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

$container = \Src\DI\Container::getInstance();

// TODO - убрать обращение к контейнеру
$entityManager = $container->get(EntityManager::class);

try {
    ConsoleRunner::run(
        new SingleManagerProvider($entityManager)
    );
} catch (Throwable $e) {
    Console::write('Во время создания базы данных возникла ошибка', Console::COLOR_RED);
    return;
}

Console::write('База данных успешно создана', Console::COLOR_GREEN);
