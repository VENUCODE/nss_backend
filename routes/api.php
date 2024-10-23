<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;


// Assuming your Slim app is properly initialized
// Define a route for getting a single event
$app->get('/', function (Request $request, Response $response, $args) {
    $res = ["message" => "Server is running"];
    $response_str = json_encode($res);
    $response->getBody()->write($response_str);
    return $response->withHeader("Content-Type", "application/json");
});

$app->post('/adduser', function (Request $request, Response $response, $args) {
    // Get the request body data
    $data = json_decode($request->getBody(), true);

    $username = $data['username'] ?? null;
    $useremail = $data['useremail'] ?? null;
    $userpassword = $data['userpassword'] ?? null;
    $usernumber = $data['usernumber'] ?? null;
    $useralnumber = $data['useralnumber'] ?? null;
    $role_id = $data['role_id'] ?? null;
    $added_by = $data['added_by'] ?? null;

    $database = new db();
    $database = $database->connect();

    $stmt = $database->prepare("SELECT * FROM users WHERE user_email = :email");
    $stmt->bindParam(':email', $useremail);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $res = ["message" => "Email already exists"];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    $stmt = $database->prepare("INSERT INTO users (user_name, user_email, user_number, user_al_number) VALUES (:username, :email, :number, :alnumber)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $useremail);
    $stmt->bindParam(':number', $usernumber);
    $stmt->bindParam(':alnumber', $useralnumber);
    $stmt->execute();

    $user_id = $database->lastInsertId();

    if ($role_id) {
        $stmt = $database->prepare("INSERT INTO members (member_id, role_id, added_by) VALUES (:member_id, :role_id, :added_by)");
        $stmt->bindParam(':member_id', $user_id);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':added_by', $added_by);
        $stmt->execute();

        $hashed_password = password_hash($userpassword, PASSWORD_BCRYPT);

        $stmt = $database->prepare("INSERT INTO authusers (id, user_password) VALUES (:user_id, :user_password)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_password', $hashed_password);
        $stmt->execute();
    }

    $res = ["message" => "User created successfully"];
    $payload = json_encode($res);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/login', function (Request $request, Response $response, $args) {
    $data = json_decode($request->getBody(), true);

    $useremail = $data['useremail'] ?? "demo@example.com";
    $userpassword = $data['userpassword'] ?? "12345";

    $database = new db();
    $database = $database->connect();

    $stmt = $database->prepare("SELECT user_id FROM users WHERE user_email = :email");
    $stmt->bindParam(':email', $useremail);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        $res = ["message" => "User does not exist"];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $user_id = $stmt->fetchColumn();

    $stmt = $database->prepare("SELECT user_password FROM authusers WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        $res = ["message" => "Invalid credentials"];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    $hashed_password = $stmt->fetchColumn();

    if (!password_verify($userpassword, $hashed_password)) {
        $res = ["message" => "Invalid credentials"];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    $secret_key = $_ENV['APP_JWT_SECRET'] ?? 'default_secret'; 

    $issuedAt = new DateTime();
    $expire = $issuedAt->modify("+1 hour");

    $token = [
        "iss" => "nss.rguktong.ac.in",
        "iat" => $issuedAt->getTimestamp(),
        "exp" => $expire->getTimestamp(),
        "data" => ["user_id" => $user_id]
    ];

    $jwt = JWT::encode($token, $secret_key, "HS256");

    $res = ["message" => "Login successful", "token" => $jwt];
    $payload = json_encode($res);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
