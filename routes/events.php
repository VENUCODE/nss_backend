<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
require_once "../middleware/AuthenticationMiddleware.php";
require_once "../middleware/FileSizeMiddleware.php";
require_once "../middleware/FileExtensionMiddleware.php";

$MAX_SIZE=4*1024*1024;

$app->post("/addEvent", function (Request $request, Response $response) {
    $fileNames = $request->getAttribute('fileNames');
    $parsedBody = $request->getAttribute('parsedBody');
    $userId = $request->getAttribute('user_id'); 

    $eventName = $parsedBody['ename'];
    $ecId = $parsedBody['ec_id'];
    $hostedOn = $parsedBody['hosted_on'];
    $location = $parsedBody['location'];
    $description = $parsedBody['description'];
    $eattend = $parsedBody['eattend'] ?? []; 
    
    $database = new db();
    $dbConnection = $database->connect();

    try {
        $dbConnection->beginTransaction();

        $stmt = $dbConnection->prepare("INSERT INTO events (event_name, ec_id, hosted_on, location, description) VALUES (:event_name, :ec_id, :hosted_on, :location, :description)");
        $stmt->bindParam(':event_name', $eventName);
        $stmt->bindParam(':ec_id', $ecId);
        $stmt->bindParam(':hosted_on', $hostedOn);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
        
        $eventId = $dbConnection->lastInsertId();

        if (!empty($fileNames)) {
            $photoStmt = $dbConnection->prepare("INSERT INTO event_photos (event_id, photo_url) VALUES (:eid, :photo_url)");
            foreach ($fileNames as $photoUrl) {
                $photoStmt->bindParam(':eid', $eventId);
                $photoStmt->bindParam(':photo_url',"/uploads/event_photos/".$photoUrl);
                $photoStmt->execute();
            }
        }

        if (!empty($eattend)) {
            $attendeeStmt = $dbConnection->prepare("INSERT INTO event_attendees (event_id, member_id) VALUES (:eid, :ettend_id)");
            foreach ($eattend as $attendeeId) {
                $attendeeStmt->bindParam(':eid', $eventId);
                $attendeeStmt->bindParam(':ettend_id', $attendeeId);
                $attendeeStmt->execute();
            }
        }

        $dbConnection->commit();

        $response->getBody()->write(json_encode(['message' => 'Event added successfully']));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

    } catch (Exception $e) {
        $dbConnection->rollBack();
        $response->getBody()->write(json_encode(['error' => 'Failed to add event: ' . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})
->add($AuthMiddleware)
->add(fileExtensionMiddleware(['png', 'jpg', 'jpeg']))
->add(fileSizeMiddleware($MAX_SIZE))
->add(UploadMiddleware(dirname(__DIR__,1)."/uploads/temp"));

$app->group('/events', function (RouteCollectorProxy $group) use($AuthMiddleware) {
    $group->get('/event[/{event_id}]', function (Request $request, Response $response, $args) {
        $eventId = $args['event_id'] ?? null;
      
        if ($eventId) {
            $sql = "SELECT * FROM (select event_id,ec.ec_name,e.ec_id,event_name,hosted_on,location from events e inner join event_category  ec on e.ec_id=ec.ec_id) res WHERE res.event_id = :event_id";
            try {
                $database = new db();
                $database = $database->connect();
                $stmt = $database->prepare($sql);
                $stmt->bindParam(':event_id', $eventId);
                $stmt->execute();

                $event = $stmt->fetch(PDO::FETCH_OBJ);
                if ($event) {
                    $response->getBody()->write(json_encode($event));
                } else {
                    $response->getBody()->write(json_encode(['message' => 'Event not found']));
                }
                return $response->withStatus(200)->withHeader("Content-Type", "application/json");
            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
                return $response->withStatus(500)->withHeader("Content-Type", "application/json");
            }
        } else {
            // Fetch all events if event_id is not provided
            $sql = "SELECT * FROM (select event_id,ec.ec_name,e.ec_id,event_name,hosted_on,location from events e inner join event_category  ec on e.ec_id=ec.ec_id) res ";
           
            try {
                $database = new db();
                $database = $database->connect();
                $stmt = $database->prepare($sql);
                $stmt->execute();

                $events = $stmt->fetchAll(PDO::FETCH_OBJ);
                $response->getBody()->write(json_encode($events));
                return $response->withStatus(200)->withHeader("Content-Type", "application/json");
            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
                return $response->withStatus(500)->withHeader("Content-Type", "application/json");
            }
        }
    })->setName('event');
    $group->get('/getattendees',function(Request $request,Response $response){
        $sql = "select user_id, user_name FROM users inner join members on member_id=user_id";
        try {
            $database = new db();
            $database = $database->connect();
            $stmt = $database->prepare($sql);
            $stmt->execute();
            $categories=$stmt->fetchAll(PDO::FETCH_OBJ);
            $response->getBody()->write(json_encode($categories));
            return $response->withStatus(200)->withHeader("Content-Type", "application/json");
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    });
    $group->get('/category/{category_id}', function (Request $request, Response $response, $args) {
        $categoryId = $args['category_id'];
        $sql = "SELECT * FROM (select event_id,ec.ec_name,e.ec_id,event_name,hosted_on,location from events e inner join event_category  ec on e.ec_id=ec.ec_id) res WHERE res.ec_id= :category_id order by hosted_on desc";
        try {
            $database = new db();
            $database = $database->connect();
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':category_id', $categoryId);
            $stmt->execute();

            $events = $stmt->fetchAll(PDO::FETCH_OBJ);
            if ($events) {
                $response->getBody()->write(json_encode($events));
            } else {
                $response->getBody()->write(json_encode(['message' => 'No events found for this category']));
            }
            return $response->withStatus(200)->withHeader("Content-Type", "application/json");
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    
    })->setName('category');
    
   
    
    $group->get('/getcategories',function(Request $request,Response $response){
        $sql = "SELECT ec_id,ec_name FROM event_category order by ec_id";
        try {
            $database = new db();
            $database = $database->connect();
            $stmt = $database->prepare($sql);
            $stmt->execute();

            $categories = $stmt->fetchAll(PDO::FETCH_OBJ);
            $response->getBody()->write(json_encode($categories));
            return $response->withStatus(201)->withHeader("Content-Type", "application/json");
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    });

    $group->post('/addcategory', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $categoryName = $data['category_name'] ?? null;
        $categoryName = trim(strtolower(preg_replace('/\s+/', ' ', $categoryName)));
        if (!$categoryName) {
            $response->getBody()->write(json_encode(["message"=>"Category name cant be empty",'error' => 'Category name is required']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(422);
        }
        $sql = "SELECT ec_id FROM event_category WHERE lower(ec_name) = :category_name";
        try {
            $database = new db();
            $database = $database->connect();
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':category_name', $categoryName);
            $stmt->execute();
            $category = $stmt->fetch(PDO::FETCH_OBJ);
            if ($category) {
                $response->getBody()->write(json_encode(["message"=>"Category already exists",'error' => 'Category already exists']));
                return $response->withHeader("Content-Type", "application/json")->withStatus(400);
            }

            $sql = "INSERT INTO event_category (ec_name) VALUES (:category_name)";
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':category_name', $categoryName);
            $stmt->execute();

            $response->getBody()->write(json_encode(['message' => 'Category added successfully']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(201);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["message"=>"failed to add Category",'error' => ['text' => $e->getMessage()]]));
            return $response->withHeader("Content-Type", "application/json")->withStatus(500);
        }
    })->add($AuthMiddleware);

    $group->put('/updatecategory', function (Request $request, Response $response, $args) {
        $data = $request->getParsedBody();
        $ecId = $data['ec_id'] ?? null;
        $ecName = $data['ec_name'] ?? null;
        $newName = $data['new_name'] ?? null;
        $newName = trim(strtolower(preg_replace('/\s+/', ' ', $newName)));
        if (!$newName) {
            $response->getBody()->write(json_encode(['error' => 'New category name is required']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(422);
        }
        if($ecName===$newName){
            $response->getBody()->write(json_encode(['error' => 'New category name is the same as the old one']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(422);
        }
        $ecId = (int)$ecId;
        $sql = "SELECT ec_id FROM event_category WHERE ec_id = :ec_id";
        try {
            $database = new db();
            $database = $database->connect();
            $stmt = $database->prepare($sql);
        
            $stmt->bindParam(':ec_id', $ecId);
            $stmt->execute();
            $category = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$category) {
                $response->getBody()->write(json_encode(['error' => 'Category does not exist']));
                return $response->withHeader("Content-Type", "application/json")->withStatus(422);
            }
            $sql = "SELECT ec_id FROM event_category WHERE lower(ec_name) = :new_name";
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':new_name', $newName);
            $stmt->execute();
            $existingCategory = $stmt->fetch(PDO::FETCH_OBJ);
            if ($existingCategory) {
                $response->getBody()->write(json_encode(['error' => 'New category name already exists']));
                return $response->withHeader("Content-Type", "application/json")->withStatus(422);
            }
            $sql = "UPDATE event_category SET ec_name = :new_name WHERE ec_id = :ec_id";
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':new_name', $newName);
            $stmt->bindParam(':ec_id', $ecId);
            $stmt->execute();
            $response->getBody()->write(json_encode(['message' => 'Category updated successfully']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(200);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
            return $response->withHeader("Content-Type", "application/json")->withStatus(500);
        }
    })->setName('updatecategory');
    
});