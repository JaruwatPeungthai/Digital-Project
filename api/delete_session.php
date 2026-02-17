<?php
session_start();
include("../config.php");

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['teacher_id'])) {
  echo json_encode(["status" => "error", "error" => "no permission"]);
  exit;
}

$teacherId = $_SESSION['teacher_id'];

// Get session_id from JSON or POST
$data = json_decode(file_get_contents("php://input"), true);
$sessionId = $data['session_id'] ?? intval($_POST['session_id'] ?? 0);

if (!$sessionId) {
  echo json_encode(["status" => "error", "error" => "ไม่พบ session_id"]);
  exit;
}

/* ตรวจสิทธิ์ว่า session นี้เป็นของอาจารย์ที่ login */
$chk = $conn->prepare("
  SELECT id
  FROM attendance_sessions
  WHERE id = ? AND teacher_id = ?
");
$chk->bind_param("ii", $sessionId, $teacherId);
$chk->execute();

if ($chk->get_result()->num_rows === 0) {
  echo json_encode(["status" => "error", "error" => "ไม่พบ session"]);
  exit;
}

/* ลบ attendance_logs ที่อิงกับ session นี้ก่อน */
$delLogs = $conn->prepare("
  DELETE FROM attendance_logs
  WHERE session_id = ?
");
$delLogs->bind_param("i", $sessionId);
if (!$delLogs->execute()) {
  echo json_encode(["status" => "error", "error" => "ไม่สามารถลบข้อมูลการเช็คชื่อ"]);
  exit;
}

/* ลบ session */
$delSession = $conn->prepare("
  DELETE FROM attendance_sessions
  WHERE id = ?
");
$delSession->bind_param("i", $sessionId);
if (!$delSession->execute()) {
  echo json_encode(["status" => "error", "error" => "ไม่สามารถลบ session"]);
  exit;
}

echo json_encode(["status" => "success", "message" => "ลบ session สำเร็จ"]);
exit;
?>
