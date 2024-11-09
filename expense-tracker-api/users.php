<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json"); 

require 'connection.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $email = $data['email'];
    $password = $data['password'];

    if (empty($email) || empty($password)) {
        echo json_encode(["error" => "Email and password are required"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            echo json_encode(["success" => true, "user_id" => $user['id']]);//login success
        } else {
            echo json_encode(["success" => "false"]);//login failed
        }
    } else {

        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $password);
        if ($stmt->execute()) {
            echo json_encode(["message" => "User created and logged in", "user_id" => $conn->insert_id]);//new user created
        } else {
            echo json_encode(["error" => "Failed to create user"]);//failed creating a new user
        }
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);    
}
?>
