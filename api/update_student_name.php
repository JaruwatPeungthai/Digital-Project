<?php
header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/../config.php";

$data = json_decode(file_get_contents("php://input"), true);
$lineId = $data['line_user_id'] ?? '';
$name   = trim($data['full_name'] ?? '');

if ($name === '') {
  echo json_encode(["message"=>"ชื่อห้ามว่าง"]);
  exit;
}

$stmt = $conn->prepare("
  UPDATE students s
  JOIN users u ON s.user_id = u.id
  SET s.full_name=?
  WHERE u.line_user_id=?
");
$stmt->bind_param("ss", $name, $lineId);
$stmt->execute();

echo json_encode(["message"=>"อัปเดตชื่อเรียบร้อย"]);
