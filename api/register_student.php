<?php
header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/../config.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  echo json_encode(["success"=>false,"message"=>"Invalid data"]);
  exit;
}

$conn->begin_transaction();

$stmt = $conn->prepare("
  INSERT INTO users (line_user_id)
  VALUES (?)
");
$stmt->bind_param("s", $data['line_user_id']);
$stmt->execute();
$user_id = $stmt->insert_id;

$stmt = $conn->prepare("
  INSERT INTO students (user_id, student_code, full_name, class_group)
  VALUES (?,?,?,?)
");
$stmt->bind_param(
  "isss",
  $user_id,
  $data['code'],
  $data['name'],
  $data['major']
);
$stmt->execute();

$conn->commit();

echo json_encode([
  "success" => true,
  "message" => "สมัครนักศึกษาสำเร็จ"
]);
