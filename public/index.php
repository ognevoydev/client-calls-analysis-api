<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Src\Controller\ApiController;

$app = AppFactory::create();

$app->post('/api/', [ApiController::class, "uploadRecord"]);

$app->run();