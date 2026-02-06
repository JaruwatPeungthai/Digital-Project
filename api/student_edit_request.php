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
$newCode = $data['student_code'] ?? '';
$newName = $data['full_name'] ?? '';
$newClass = $data['class_group'] ?? '';

if (empty($lineId)) {
  echo json_encode(["status" => "error", "message" => "Missing line_user_id"]);
  exit;
}

// ดึงข้อมูลนักศึกษา
$stmt = $conn->prepare("
  SELECT st.user_id, st.student_code, st.full_name, st.class_group, st.advisor_id
  FROM users u
  JOIN students st ON u.id = st.user_id
  WHERE u.line_user_id = ?
");

if (!$stmt) {
  echo json_encode(["status" => "error", "message" => "Database prepare failed"]);
  exit;
}

$stmt->bind_param("s", $lineId);
if (!$stmt->execute()) {
  echo json_encode(["status" => "error", "message" => "Database execute failed"]);
  exit;
}

$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
  echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลนักศึกษา"]);
  exit;
}

// สร้าง request_id
$requestId = "REQ" . date("YmdHis") . rand(1000, 9999);

// บันทึก request
$insertStmt = $conn->prepare("
  INSERT INTO student_edit_requests 
  (request_id, student_id, requested_by, old_student_code, old_full_name, old_class_group, 
   new_student_code, new_full_name, new_class_group)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$insertStmt) {
  echo json_encode(["status" => "error", "message" => "Database prepare failed"]);
  exit;
}

$requestedBy = $student['advisor_id'] ? "advisor_" . $student['advisor_id'] : "faculty";

$insertStmt->bind_param(
  "sisssssss",
  $requestId,
  $student['user_id'],
  $requestedBy,
  $student['student_code'],
  $student['full_name'],
  $student['class_group'],
  $newCode,
  $newName,
  $newClass
);

if ($insertStmt->execute()) {
  echo json_encode([
    "status" => "success",
    "message" => "ส่งคำขอแก้ไขข้อมูลสำเร็จ",
    "request_id" => $requestId
  ]);
} else {
  echo json_encode(["status" => "error", "message" => "Database insert failed"]);
}
?>
