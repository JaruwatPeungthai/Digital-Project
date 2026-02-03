<?php
header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/../config.php";

$data = json_decode(file_get_contents("php://input"), true);
$lineId = $data['line_user_id'] ?? '';

$stmt = $conn->prepare("
  SELECT s.student_code, s.full_name, s.class_group
  FROM users u
  JOIN students s ON u.id = s.user_id
  WHERE u.line_user_id=?
");
$stmt->bind_param("s", $lineId);
$stmt->execute();

$result = $stmt->get_result()->fetch_assoc();
echo json_encode($result);
