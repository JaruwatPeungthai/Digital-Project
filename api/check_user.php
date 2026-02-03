<?php
header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/../config.php";

$data = json_decode(file_get_contents("php://input"), true);
$lineId = $data['line_user_id'] ?? '';

if ($lineId === '') {
  echo json_encode(["registered" => false]);
  exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE line_user_id=?");
$stmt->bind_param("s", $lineId);
$stmt->execute();

echo json_encode([
  "registered" => $stmt->get_result()->num_rows > 0
]);
