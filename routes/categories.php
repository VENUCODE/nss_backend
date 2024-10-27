<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\CategoryController;

$app->group('/categories', function (RouteCollectorProxy $group) {
    $group->get('', [CategoryController::class, 'getCategories'])->setName('getCategories');
    $group->post('/add', [CategoryController::class, 'addCategory'])->setName('addCategory');
    $group->put('/update', [CategoryController::class, 'updateCategory'])->setName('updateCategory');
});
