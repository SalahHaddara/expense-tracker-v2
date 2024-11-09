<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require 'connection.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $where_clauses = array();
        $params = array();

        if (isset($_GET['type']) && $_GET['type'] !== 'all') {
            $where_clauses[] = "type = ?";
            $params[] = $_GET['type'];
        }
        if (isset($_GET['minAmount'])) {
            $where_clauses[] = "amount >= ?";
            $params[] = $_GET['minAmount'];
        }
        if (isset($_GET['maxAmount'])) {
            $where_clauses[] = "amount <= ?";
            $params[] = $_GET['maxAmount'];
        }
        if (isset($_GET['date'])) {
            $where_clauses[] = "date = ?";
            $params[] = $_GET['date'];
        }
        if (isset($_GET['searchText'])) {
            $where_clauses[] = "notes LIKE ?";
            $params[] = "%" . $_GET['searchText'] . "%";
        }
        if (isset($_GET['userId'])) {
            $where_clauses[] = "user_id = ?";
            $params[] = $_GET['userId'];
        }
        if (isset($_GET['id'])) {
            $where_clauses[] = "id = ?";
            $params[] = $_GET['id'];
        }


        $sql = "SELECT * FROM transactions";
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $sql .= " ORDER BY date DESC";

        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = array();
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        echo json_encode($transactions);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['date'])) {
            $stmt = $conn->prepare("INSERT INTO transactions (amount, type, date, notes, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("dsssi", $data['amount'], $data['type'], $data['date'], $data['notes'], $data['user_id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO transactions (amount, type, notes, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("dssi", $data['amount'], $data['type'], $data['notes'], $data['user_id']);
        }

        if ($stmt->execute()) {
            $data['id'] = $stmt->insert_id;
            echo json_encode($data);
        } else {
            http_response_code(400);
            echo json_encode(array("error" => $stmt->error));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $_GET['id'];

        $stmt = $conn->prepare("UPDATE transactions SET amount=?, type=?, notes=? WHERE id=?");
        $stmt->bind_param("dssi", $data['amount'], $data['type'], $data['notes'], $id);

        if ($stmt->execute()) {
            echo json_encode($data);
        } else {
            http_response_code(400);
            echo json_encode(array("error" => $stmt->error));
        }
        break;

    case 'DELETE':
        $id = $_GET['id'];

        $stmt = $conn->prepare("DELETE FROM transactions WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(array("message" => "Transaction deleted"));
        } else {
            http_response_code(400);
            echo json_encode(array("error" => $stmt->error));
        }
        break;
}
