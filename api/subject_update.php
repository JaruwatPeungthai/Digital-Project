<?php
session_start();
include("../config.php");

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['teacher_id'])) {
  echo json_encode(["status" => "error", "message" => "ไม่ได้เข้าสู่ระบบ"]);
  exit;
}

$teacher_id = $_SESSION['teacher_id'];
$subject_id = intval($_POST['subject_id'] ?? 0);
$subject_name = trim($_POST['subject_name'] ?? '');
$subject_code = trim($_POST['subject_code'] ?? '');
$section = trim($_POST['section'] ?? '');
$years = trim($_POST['years'] ?? '');
$semester = trim($_POST['semester'] ?? '');

// Validate inputs
if (!$subject_id || !$subject_name || !$subject_code || !$section || !$years || !$semester) {
  echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบ"]);
  exit;
}

// Get current subject data
$currentStmt = $conn->prepare("
  SELECT subject_name, subject_code, section, years, semester, hash
  FROM subjects 
  WHERE subject_id = ? AND teacher_id = ?
");
$currentStmt->bind_param("ii", $subject_id, $teacher_id);
$currentStmt->execute();
$currentResult = $currentStmt->get_result()->fetch_assoc();

if (!$currentResult) {
  echo json_encode(["status" => "error", "message" => "ไม่พบรายวิชาที่ต้องการแก้ไข"]);
  exit;
}

// Generate hash for new data
$newHash = generateSubjectHash($subject_name, $subject_code, $section, $years, $semester);

// Check if data is identical to original (by comparing hash)
if ($newHash === $currentResult['hash']) {
  echo json_encode(["status" => "error", "message" => "ข้อมูลไม่มีการเปลี่ยนแปลง"]);
  exit;
}

// Check for duplicate with other subjects (excluding current) by hash
$dupStmt = $conn->prepare("
  SELECT subject_id FROM subjects
  WHERE teacher_id = ? 
    AND hash = ? 
    AND subject_id != ?
");
$dupStmt->bind_param("isi", $teacher_id, $newHash, $subject_id);
$dupStmt->execute();
$dupResult = $dupStmt->get_result();

if ($dupResult->num_rows > 0) {
  echo json_encode(["status" => "error", "message" => "มีรายวิชาที่มีข้อมูลตรงกันอยู่แล้ว"]);
  exit;
}

// Update subject with new hash
$updateStmt = $conn->prepare("
  UPDATE subjects 
  SET subject_name = ?, subject_code = ?, section = ?, years = ?, semester = ?, hash = ?
  WHERE subject_id = ? AND teacher_id = ?
");
$updateStmt->bind_param("ssssssii", $subject_name, $subject_code, $section, $years, $semester, $newHash, $subject_id, $teacher_id);

if ($updateStmt->execute()) {
  echo json_encode(["status" => "success", "message" => "แก้ไขรายวิชาสำเร็จ"]);
} else {
  echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดในการแก้ไข"]);
}
?>
