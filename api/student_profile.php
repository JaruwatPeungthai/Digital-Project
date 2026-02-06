<?php
error_reporting(0);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=utf-8");

if (!file_exists(__DIR__ . "/../config.php")) {
  die(json_encode(["error" => "Config file not found"]));
}

require __DIR__ . "/../config.php";

if (!isset($conn) || !$conn) {
  die(json_encode(["error" => "Database connection failed"]));
}

$data = json_decode(file_get_contents("php://input"), true);
$lineId = $data['line_user_id'] ?? '';

if (empty($lineId)) {
  echo json_encode(["error" => "Missing line_user_id"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT s.student_code, s.full_name, s.class_group, s.advisor_id, t.full_name as advisor_name
  FROM users u
  JOIN students s ON u.id = s.user_id
  LEFT JOIN teachers t ON s.advisor_id = t.id
  WHERE u.line_user_id = ?
");

if (!$stmt) {
  echo json_encode(["error" => "Database prepare failed"]);
  exit;
}

$stmt->bind_param("s", $lineId);
if (!$stmt->execute()) {
  echo json_encode(["error" => "Database execute failed"]);
  exit;
}

$result = $stmt->get_result()->fetch_assoc();
if ($result) {
  echo json_encode($result);
} else {
  echo json_encode(["error" => "Student not found"]);
}
?>
