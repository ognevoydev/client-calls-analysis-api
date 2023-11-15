<?php

use Slim\App;
use Src\Controller\ApiController;

return function (App $app) {
    $app->post('/api/', [ApiController::class, "uploadRecord"]);
};


