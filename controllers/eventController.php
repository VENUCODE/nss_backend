<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;
use Throwable;

class EventController {
    public function getEvents(Request $request, Response $response, $args) {
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
    }

    public function upload (Request $request, Response $response) {
        $files = $request->getUploadedFiles();
        $uploadDirectory = dirname(__DIR__, 1) . '/uploads/event_photos/';
    
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }
    
        if (!array_key_exists('event_photos', $files)) {
            $response->getBody()->write(json_encode("Files do not exist with name event_photos"));
            return $response->withHeader("Content-Type", "application/json")->withStatus(422);
        }

        if (!is_array($files['event_photos'])) {
            $files['event_photos'] = [$files['event_photos']];
        }
        $allowedTypes = ['image/png', 'image/jpeg'];
        $uploadedFilePaths = [];
    
        foreach ($files['event_photos'] as $file) {
            $error = $file->getError();
            $type = $file->getClientMediaType();
            
            if ($error !== UPLOAD_ERR_OK) {
                $response->getBody()->write(json_encode(['error' => 'Upload failed due to server error', 'errorcode' => $error]));
                return $response->withHeader("Content-Type", "application/json")->withStatus(422);
            }
    
            if (!in_array($type, $allowedTypes)) {
                $response->getBody()->write(json_encode(['error' => 'File type not allowed', 'type' => $type]));
                return $response->withHeader("Content-Type", "application/json")->withStatus(422);
            }
    
            if ($file->getSize() > 3000000) {
                $response->getBody()->write(json_encode(['error' => 'File size exceeds the limit of 3MB']));
                return $response->withHeader("Content-Type", "application/json")->withStatus(422);
            }
    
            try {
                $filename = moveUploadedFile($uploadDirectory, $file);
                 $uploadedFilePaths[] = "/uploads/event_photos/" . ltrim($filename, '/');
            } catch (Throwable $th) {
                $response->getBody()->write(json_encode(['error' => 'One or more files could not be uploaded']));
                return $response->withHeader("Content-Type", "application/json")->withStatus(500);
            }
        }
    
        $response->getBody()->write(json_encode(['files' => $uploadedFilePaths]));
        return $response->withHeader("Content-Type", "application/json")->withStatus(201);
    }

    public function getAttendees(Request $request,Response $response){
        $sql = "select user_name,role_id FROM users inner join members on member_id=user_id";
        try {
            $database = new db();
            $database = $database->connect();
            $stmt = $database->prepare($sql);
            $stmt->execute();

            $categories = $stmt->fetchAll(PDO::FETCH_OBJ);
            $response->getBody()->write(json_encode($categories));
            return $response->withStatus(200)->withHeader("Content-Type", "application/json");
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }
    public function getByCategory (Request $request, Response $response, $args) {
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
    
    }
}
