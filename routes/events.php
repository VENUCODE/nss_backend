<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Route for fetching a specific event by event ID (optional)
$app->get('/event/all[/{event_id}]', function (Request $request, Response $response, $args) {
    $eventId = $args['event_id'] ?? null;
    // If event_id is provided, fetch the specific event; otherwise, fetch all events
    if ($eventId) {
        $sql = "SELECT * FROM events WHERE event_id = :event_id";
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
        $sql = "SELECT * FROM events";
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
});

// Route for fetching events by category ID (optional)
$app->get('/event/category[/{category_id}]', function (Request $request, Response $response, $args) {
    $categoryId = $args['category_id'] ?? null;

    if ($categoryId) {
        $sql = "SELECT * FROM events WHERE ec_id = :category_id";
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
    } else {
       $sql = "SELECT *  FROM events order by hosted_on desc";
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
});