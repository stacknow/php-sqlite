<?php
// Database connection
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create users table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE
)");

// Helper function to send JSON responses
function sendJson($data, $statusCode = 200) {
    header("Content-Type: application/json");
    http_response_code($statusCode);
    echo json_encode($data);
}

// Handle different HTTP requests
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Get all users
if ($method == 'GET' && $path == '/users') {
    $stmt = $db->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendJson($users);
}

// Create a new user
elseif ($method == 'POST' && $path == '/users') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['name']) || !isset($input['email'])) {
        sendJson(["error" => "Invalid input"], 400);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        $stmt->bindParam(':name', $input['name']);
        $stmt->bindParam(':email', $input['email']);
        $stmt->execute();
        $id = $db->lastInsertId();
        sendJson(["message" => "User created", "user" => ["id" => $id, "name" => $input['name'], "email" => $input['email']]]);
    } catch (PDOException $e) {
        sendJson(["error" => $e->getMessage()], 500);
    }
}

// Handle invalid routes
else {
    sendJson(["error" => "Route not found"], 404);
}
?>
