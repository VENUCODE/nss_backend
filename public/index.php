<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;


use Slim\Psr7\Factory\ResponseFactory;
use Slim\Exception\HttpNotFoundException;

// use DI\ContainerBuilder;
// use Psr\Http\Message\UploadedFileInterface;

use Slim\Factory\AppFactory;


require "../vendor/autoload.php";

// $containerBuilder = new ContainerBuilder();
// $container = $containerBuilder->build();
// $container->set('upload_directory', __DIR__ . '../uploads');

// AppFactory::setContainer($container);

$app = AppFactory::create();


// config ,routes include section
require "../config/db.php";

//middleware adding section
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();


$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
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


// Route that checks if a file exists in the uploads directory

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $response = $response->withStatus(404);
    $response->getBody()->write(json_encode(['message'=>'Route not Found']));
    return $response->withHeader('Content-Type','Application/json');
});

$app->run();