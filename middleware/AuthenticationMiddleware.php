<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key as JWTKey;

$AuthMiddleware =function(Request $request, Handler $handler) use($app) {
    $authHeader = $request->getHeaderLine('Authorization');

    if (!$authHeader) {
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode(['message' => 'Unauthorized access 1']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    $token = $authHeader;
    
    if (!$token) {
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode(['message' => 'Unauthorized access  2']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    try {
        $decoded = FirebaseJWT::decode($token, new JWTKey('rgukt@679@nss', 'HS256'));
    } catch (FirebaseJWT\ExpiredException $e) {
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode(['message' => 'Unauthorized access 3']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    } catch (FirebaseJWT\SignatureInvalidException $e) {
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode(['message' => 'Unauthorized access 4']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response = $app->getResponseFactory()->createResponse();
      
        $response->getBody()->write(json_encode(['message' => 'Unauthorized access 5', 'error' => $e->getMessage()]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            
    }

    $decoded_data = $decoded->data; 
    $user_id = $decoded_data->user_id; 
    $request = $request->withAttribute('user_id', $user_id);
    return $handler->handle($request);
};
