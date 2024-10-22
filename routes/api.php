<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Define a route for getting a single event
$app->get('', function (Request $request, Response $response, $args) {
    // Prepare the response data
    $res = ["message" => "this is the message from server"];
    
    // Encode the response data as JSON
    $response_str = json_encode($res);
    
    // Write the response data to the response body
    $response->getBody()->write($response_str);
    
    // Set the Content-Type header to application/json and return the response
    return $response->withHeader("Content-Type", "application/json");
});
