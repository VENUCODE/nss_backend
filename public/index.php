<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Factory\AppFactory;

require "../vendor/autoload.php";
$app = AppFactory::create();
// config ,routes include section
require "../config/db.php";


//middleware adding section
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(function (Request $request, Handler $handler) {
    return $handler->handle($request);
});

//FIXME - change true to false for first parameter
$app->addErrorMiddleware(true, true, true);
require "../routes/api.php";
require "../routes/events.php";


$app->run();