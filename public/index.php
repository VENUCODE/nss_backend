<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Factory\AppFactory;


require "../vendor/autoload.php";
$app = AppFactory::create();
// config ,routes include section
require "../config/db.php";



//middleware adding section
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

//  CORS Middleware
$app->add(function (Request $request, Handler $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});
$beforeMiddleware = function (Request $request, Handler $handler) use ($app) {
    // Example: Check for a specific header before proceeding
    $auth = $request->getHeaderLine('Authorization');
    if (!$auth) {
        // Short-circuit and return a response immediately
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode(['message'=>'unautherized access']));
        
        return $response->withStatus(401)->withHeader('Content-Type','Application/json');
    }

    // Proceed with the next middleware
    return $handler->handle($request);
};

//FIXME - change true to false for first parameter
$app->addErrorMiddleware(true, true, true);
// $app->add($beforeMiddleware);
require "../routes/api.php";
require "../routes/events.php";


$app->run();