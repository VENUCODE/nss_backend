<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
function moveUploadedFile($directory, $uploadedFile) {
    $filename = sprintf('%s.%s', bin2hex(random_bytes(8)), pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    return $filename;
}
$app->group('/events', function (RouteCollectorProxy $group) {
    $group->get('/event[/{event_id}]', function (Request $request, Response $response, $args) {
        $eventId = $args['event_id'] ?? null;
        // If event_id is provided, fetch the specific event; otherwise, fetch all events
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

    $group->get('/category/{category_id}', function (Request $request, Response $response, $args) {
        $categoryId = $args['category_id'];
        $sql = "SELECT * FROM (select event_id,ec.ec_name,e.ec_id,event_name,hosted_on,location from events e inner join event_category  ec on e.ec_id=ec.ec_id) res WHERE res.ec_id= :category_id";
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
    $group->post('/upload', function (Request $request, Response $response) {
        $files = $request->getUploadedFiles();
        $uploadDirectory = __DIR__ . '/uploads/';
    
        // Ensure the directory exists
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);  // Create directory with write permissions
        }
    
        if (!array_key_exists('file', $files)) {
            $response->getBody()->write(json_encode("File does not exist with name file"));
            return $response->withHeader("Content-Type", "application/json")->withStatus(422);
        }
    
        $file = $files['file'];
        $error = $file->getError();
        $type = $file->getClientMediaType();
    
        if ($error!=0) {
            $response->getBody()->write(json_encode(['error' => 'Upload failed due to server error', 'errorcode' => $error]));
            return $response->withStatus(422)->withHeader("Content-Type", "application/json");
        }
    
        // Example of additional checks (commented)
        $allowedTypes = ['image/png', 'image/jpeg'];
        if (!in_array($type, $allowedTypes)) {
            $response->getBody()->write(json_encode(['error' => 'File type not allowed', 'type' => $type]));
            return $response->withStatus(422)->withHeader("Content-Type", "application/json");
        }
        if ($file->getSize() > 3000000) {
            $response->getBody()->write(json_encode(['error' => 'File size exceeds the limit of 3MB']));
            return $response->withStatus(422)->withHeader("Content-Type", "application/json");
        }
        try {
            $filename = moveUploadedFile($uploadDirectory, $file);
            $response->getBody()->write(json_encode(['filename' =>$uploadDirectory.$filename]));
            return $response->withStatus(201)->withHeader("Content-Type", "application/json");
        } catch (Throwable $th) {
            $response->getBody()->write(json_encode(['error' => 'File could not be uploaded']));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    })->setName('upload');


});