<?php

require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;

// Создание DI-контейнера
$container = \Src\DI\Container::getInstance();

// Создание приложения
AppFactory::setContainer($container);
$app = AppFactory::create();

// Установка маршрутов
$routes = require_once __DIR__ . '/Routes.php';
$routes($app);

return $app;