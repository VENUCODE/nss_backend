<?php

use Slim\App;
use App\Controllers\EventController;
use Slim\Routing\RouteCollectorProxy;
$app->group('/events', function (RouteCollectorProxy $group) {
    $group->get("/hello",[EventController::class,'message'])->setName("message");
    $group->get('/event[/{event_id}]', [EventController::class, 'getEvents'])->setName('event');
    $group->get('/getattendees', [EventController::class, 'getAttendees'])->setName("getattendees");
    $group->post('/upload', [EventController::class, 'upload'])->setName('upload');
    $group->get("/category/{category_id}", [EventController::class, "getByCategory"]);
});
