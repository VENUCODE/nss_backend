<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class CategoryController {
    public function getCategories(Request $request, Response $response) {
        $sql = "SELECT ec_id, ec_name FROM event_category ORDER BY ec_id";
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
    public function addCategory(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $categoryName = $data['category_name'] ?? null;
        $categoryName = trim(strtolower(preg_replace('/\s+/', ' ', $categoryName)));
        if (!$categoryName) {
            $response->getBody()->write(json_encode(['error' => 'Category name is required']));
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
                $response->getBody()->write(json_encode(['error' => 'Category already exists']));
                return $response->withHeader("Content-Type", "application/json")->withStatus(422);
            }

            $sql = "INSERT INTO event_category (ec_name) VALUES (:category_name)";
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':category_name', $categoryName);
            $stmt->execute();

            $response->getBody()->write(json_encode(['message' => 'Category added successfully']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(201);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => ['text' => $e->getMessage()]]));
            return $response->withHeader("Content-Type", "application/json")->withStatus(500);
        }
    }
    public function  updateCategory(Request $request, Response $response) {
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
    }
}
