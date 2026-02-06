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

session_start();
if (!isset($_SESSION['teacher_id'])) {
  echo json_encode(["status" => "error", "message" => "Unauthorized"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$requestId = $data['request_id'] ?? '';
$action = $data['action'] ?? ''; // approve or reject
$teacherId = $_SESSION['teacher_id'];

// ดึงข้อมูล request
$stmt = $conn->prepare("
  SELECT * FROM student_edit_requests
  WHERE request_id = ? AND requested_by = ?
");

if (!$stmt) {
  echo json_encode(["status" => "error", "message" => "Database prepare failed"]);
  exit;
}

$advisorPrefix = "advisor_" . $_SESSION['teacher_id'];
$stmt->bind_param("ss", $requestId, $advisorPrefix);
if (!$stmt->execute()) {
  echo json_encode(["status" => "error", "message" => "Database execute failed"]);
  exit;
}
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
  echo json_encode(["status" => "error", "message" => "ไม่พบคำขอนี้"]);
  exit;
}

if ($action === "approve") {
  // อัปเดตข้อมูลนักศึกษา
  $updateStmt = $conn->prepare("
    UPDATE students
    SET student_code = ?, full_name = ?, class_group = ?
    WHERE user_id = ?
  ");
  
  if (!$updateStmt) {
    echo json_encode(["status" => "error", "message" => "Database prepare failed"]);
    exit;
  }
  
  $updateStmt->bind_param(
    "sssi",
    $request['new_student_code'],
    $request['new_full_name'],
    $request['new_class_group'],
    $request['student_id']
  );

  if (!$updateStmt->execute()) {
    echo json_encode(["status" => "error", "message" => "Failed to update student data"]);
    exit;
  }
} 

// อัปเดต status ของ request
$statusValue = ($action === "approve") ? "approved" : "rejected";
$updateRequestStmt = $conn->prepare("
  UPDATE student_edit_requests
  SET status = ?, reviewed_at = NOW(), reviewed_by = ?
  WHERE request_id = ?
");

if (!$updateRequestStmt) {
  echo json_encode(["status" => "error", "message" => "Database prepare failed"]);
  exit;
}

$updateRequestStmt->bind_param("sis", $statusValue, $_SESSION['teacher_id'], $requestId);

if ($updateRequestStmt->execute()) {
  echo json_encode([
    "status" => "success",
    "message" => ($action === "approve") ? "ยืนยันการแก้ไขสำเร็จ" : "ปฏิเสธการแก้ไขสำเร็จ"
  ]);
} else {
  echo json_encode(["status" => "error", "message" => "Failed to update request status"]);
}
?>
