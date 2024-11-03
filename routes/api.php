<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
require_once __DIR__ . "/../middleware/FileUploadMiddleware.php";
require_once __DIR__ . "/../middleware/AuthenticationMiddleware.php";

$app->get('/', function (Request $request, Response $response, $args) {
    $res = ["message" => "Server is running"];
    $response_str = json_encode($res);
    $response->getBody()->write($response_str);
    return $response->withHeader("Content-Type", "application/json");
});
// $app->get('/hash', function (Request $request, Response $response, $args) {
//     $data = json_decode($request->getBody(), true);
//     $pass = $data['password'] ?? '';
//     if (empty($pass)) {
//         $res = ["message" => "Password is required"];
//         $response_str = json_encode($res);
//         $response->getBody()->write($response_str);
//         return $response->withHeader("Content-Type", "application/json")->withStatus(422);
//     }
//     $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
//     $res = ["hashed" => $hashed_password];
//     $response_str = json_encode($res);
//     $response->getBody()->write($response_str);
//     return $response->withHeader("Content-Type", "application/json");
// });
$app->post('/adduser', function (Request $request, Response $response, $args) {
    try {
    
        $data = $request->getAttribute('parsedBody') ?? [];
        $filePaths = $request->getAttribute('fileNames') ?? [];
        $profilePhoto=NULL;
        if (!empty($filePaths)) {
           $profilePhoto=$filePaths["profilePhoto"];
        }
  
        $username = $data['username'] ?? null;
        $useremail = $data['useremail'] ?? null;
        $userpassword = $data['userpassword'] ?? null;
        $usernumber = $data['usernumber'] ?? null;
        $useralnumber = $data['useralnumber'] ?? null;
        $assign_role = $data['assign_role'] ?? null;
        $added_by = $data['added_by'] ?? null;
        $database=new db();
        $database=$database->connect();
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
        if($profilePhoto){
            $profilePhoto="/uploads/user_photos/".$profilePhoto;
            $stmt = $database->prepare("UPDATE users SET profile_photo=:profilephoto WHERE user_id=:user_id");
            $stmt->bindParam(':profilephoto', $profilePhoto);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        }
        if ($assign_role) {
            $hashed_password = password_hash($userpassword, PASSWORD_BCRYPT);
            $stmt = $database->prepare("INSERT INTO authusers (id, user_password) VALUES (:user_id, :user_password)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':user_password', $hashed_password);
            $stmt->execute();
        
            $stmt = $database->prepare("INSERT INTO members (member_id, role_id, added_by) VALUES (:member_id, :role_id, :added_by)");
            $stmt->bindParam(':member_id', $user_id);
            $rid=1;
            $stmt->bindParam(':role_id', $rid);
            $stmt->bindParam(':added_by', $added_by);
            $stmt->execute();
        }
    
        $res = ["message" => "User created successfully"];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (PDOException $e) {
        $res = ["message" => "Database error: " . $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } catch (Exception $e) {
        $res = ["message" => "Internal server error: " . $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add(UploadMiddleware(dirname(__DIR__,1)."/uploads/user_photos"));

$app->post('/addBannerImages', function (Request $request, Response $response, $args) {
    try {
        $data = $request->getAttribute('parsedBody') ?? [];
        $filePaths = $request->getAttribute('fileNames') ?? [];
        $bannerImage = NULL;
        if (!empty($filePaths)) {
            $bannerImage = $filePaths["bannerImage"];
        }

        $database = new db();
        $database = $database->connect();

        $stmt = $database->prepare("INSERT INTO banner_images (image_path) VALUES (:image_path)");
        $stmt->bindParam(':image_path', $bannerImage);
        $stmt->execute();

        $res = ["message" => "Banner image added successfully"];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (PDOException $e) {
        $res = ["message" => "Database error: " . $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } catch (Exception $e) {
        $res = ["message" => "Internal server error: " . $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($AuthMiddleware)->add(UploadMiddleware(dirname(__DIR__,1)."/uploads/banner_photos"));
$app->post('/login', function (Request $request, Response $response, $args) {
    $data = json_decode($request->getBody(), true);

    $useremail = $data['useremail'] ?? '';
    $userpassword = $data['userpassword'] ?? '';

    if (empty($useremail) || empty($userpassword)) {
        $response->getBody()->write(json_encode(["status" => false, "message" => "Insufficient credentials"]));
        return $response->withStatus(422)->withHeader("Content-type", "application/json");
    }
    $database = new db();
    $database = $database->connect();

    $stmt = $database->prepare("SELECT * FROM users WHERE user_email = :email");
    $stmt->bindParam(':email', $useremail);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        $res = ["message" => "User does not exist"];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

   $userData = $stmt->fetch();
    $user_id = $userData['user_id'];
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

    $secret_key = $_ENV['APP_JWT_SECRET'] ?? 'rgukt@679@nss';

    $issuedAt = new DateTime();
    $expire = (new DateTime())->modify("+2 hour");

    $token = [
        // "iss" => "nss.rguktong.ac.in",
        "iat" => $issuedAt->getTimestamp(),
        "exp" => $expire->getTimestamp(),
        "data" => ["user_id" => $userData['user_id']]
    ];

    $jwt = JWT::encode($token, $secret_key, "HS256");

    $res = ["message" => "Login successful", "token" => $jwt];

    $payload = json_encode($res);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
$app->get('/uploads/event_photos/{filename}', function (Request $request, Response $response,array  $args) {

    $filePath =dirname( __DIR__ ,1). '/uploads/event_photos/' . basename($args['filename']);
    
    if (!file_exists($filePath)) {
        $response->getBody()->write("File not found.".$filePath);
        return $response->withStatus(404);
    }

    $mimeType = mime_content_type($filePath);
    return $response->withHeader('Content-Type', $mimeType)
                    ->withHeader('Content-Disposition', 'inline; filename="' . basename($filePath) . '"')
                    ->withBody(new \Slim\Psr7\Stream(fopen($filePath, 'rb')));
});

$app->get('/uploads/user_photos/{filename}', function (Request $request, Response $response,array  $args) {

    $filePath =dirname( __DIR__ ,1). '/uploads/user_photos/' . basename($args['filename']);
    
    if (!file_exists($filePath)) {
        $response->getBody()->write("File not found.".$filePath);
        return $response->withStatus(404);
    }

    $mimeType = mime_content_type($filePath);
    return $response->withHeader('Content-Type', $mimeType)
                    ->withHeader('Content-Disposition', 'inline; filename="' . basename($filePath) . '"')
                    ->withBody(new \Slim\Psr7\Stream(fopen($filePath, 'rb')));
});


$app->get("/getusers", function (Request $request, Response $response) {
    $data = json_decode($request->getBody()->getContents(), true);
    $user_ids = $data['user_ids'] ?? null;

    if (empty($user_ids) || !is_array($user_ids)) {
        $response->getBody()->write(json_encode(["message" => "Invalid input", "error" => "User IDs should be a non-empty array"])); 
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $sql = "SELECT user_id, user_name, user_email, profile_photo FROM users WHERE user_id IN ($placeholders)";
    
    try {
        $database = new db();
        $database = $database->connect();
        $stmt = $database->prepare($sql);
        $stmt->execute($user_ids);
        $userData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($userData));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $res = ["message" => "Database error", "error" => $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } catch (Exception $e) {
        $res = ["message" => "Internal server error", "error" => $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});



$app->get("/getuser", function (Request $request, Response $response){
    $user_id=$request->getAttribute("user_id")??null;
    if(!$user_id){
        $response->getBody()->write(json_encode(["message" => "User not found", "error" => "Invalid user ID"])); 
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $sql="SELECT * FROM users where user_id=:userid";
    try {
        $database=new db();
        $database=$database->connect();
        $stmt = $database->prepare($sql);
        $stmt->bindParam(':userid', $user_id);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($userData));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $res = ["message" => "Database error", "error" => $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } catch (Exception $e) {
        $res = ["message" => "Internal server error", "error" => $e->getMessage()];
        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($AuthMiddleware);
