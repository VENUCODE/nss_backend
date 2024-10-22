<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/event', function (Request $request, Response $response, $args) {
    // Prepare the response data
    $res = ["message" => "this is the response message"];
    $response_str = json_encode($res);
    $response->getBody()->write($response_str);

    return $response->withHeader("Content-Type", "application/json");
});


$app->get('/event/{id}', function (Request $request, Response $response, $args) {
    $eventId = $args['id'];
    $res = ["message" => "Event with id $eventId"];
    $response_str = json_encode($res);
    $response->getBody()->write($response_str);
    return $response->withHeader("Content-Type", "application/json");
});
