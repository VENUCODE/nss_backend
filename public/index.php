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





//FIXME - change true to false for first parameter
$app->addErrorMiddleware(true, true, true);

// $app->add($beforeMiddleware);
require "../routes/api.php";
require "../routes/events.php";



$app->add(function (Request $request, Handler $handler) {
    $response = $handler->handle($request);

    // Allowing CORS headers
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

    // Handle preflight OPTIONS request
    if ($request->getMethod() === 'OPTIONS') {
        // If it is a preflight request, end here
        return $response->withStatus(204); // No Content
    }

    return $response;
});
// $app->get('/uploads/user_photos/{filename}', function (Request $request, Response $response, $args) {
//     $filename = $args['filename'];
//     $filepath = __DIR__ . '/../uploads/user_photos/' . $filename;
//     if (file_exists($filepath)) {
//         return $response->withHeader('Content-Type', 'image/jpeg')
//             ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
//             ->withBody(new \Slim\Psr7\Stream($filepath));
//     } else {
//         return $response->withStatus(404);
//     }
// });

// $app->get('/uploads/event_photos/{filename}', function (Request $request, Response $response, $args) {
//     $filename = $args['filename'];
//     $filepath = __DIR__ . '/../uploads/event_photos/' . $filename;
//     if (file_exists($filepath)) {
//         return $response->withHeader('Content-Type', 'image/jpeg')
//             ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
//             ->withBody(new \Slim\Psr7\Stream($filepath));
//     } else {
//         return $response->withStatus(404);
//     }
// });

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH','OPTIONS'], '/{routes:.+}', function (Request $request, Response $response) {
    $response = $response->withStatus(404);
    $response->getBody()->write(json_encode(['message'=>'Route not Found']));
    return $response->withHeader('Content-Type','Application/json');
});

$app->run();