<?php
namespace App\Controllers;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\EventController;

$app->group('/events', function (RouteCollectorProxy $group) {
    $group->get('/event[/{event_id}]', [EventController::class, 'getEvents'])->setName('event');
    $group->get('/getattendees', [EventController::class, 'getAttendees'])->setName("getattendees");
    $group->post('/upload', [EventController::class, 'upload'])->setName('upload');
    $group->get("/category/{category_id}", [EventController::class, "getByCategory"]);
});
