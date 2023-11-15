<?php

require __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

// Создание строителя DI-контейнера
$containerBuilder = new ContainerBuilder();

// Установка зависимостей
$dependencies = require_once __DIR__ . '/Dependencies.php';
$dependencies($containerBuilder);

// Создание DI-контейнера
$container = $containerBuilder->build();

// Создание приложения
AppFactory::setContainer($container);
$app = AppFactory::create();

// Установка маршрутов
$routes = require_once __DIR__ . '/Routes.php';
$routes($app);

return $app;