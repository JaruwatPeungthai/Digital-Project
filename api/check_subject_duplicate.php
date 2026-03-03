<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

header('Content-Type: application/json');

$teacherId = $_SESSION['teacher_id'];
$subject_name = trim($_POST['subject_name'] ?? '');
$subject_code = trim($_POST['subject_code'] ?? '');
$section = trim($_POST['section'] ?? '');
$years = trim($_POST['years'] ?? '');
$semester = trim($_POST['semester'] ?? '');

// Validate all fields are provided
if (!$subject_name || !$subject_code || !$section || !$years || !$semester) {
  echo json_encode([
    'status' => 'error',
    'message' => 'กรุณากรอกข้อมูลให้ครบทั้ง 5 ช่อง'
  ]);
  exit;
}

// Generate hash for the provided data
$hash = generateSubjectHash($subject_name, $subject_code, $section, $years, $semester);

// Check if this hash already exists for this teacher
$stmt = $conn->prepare("
  SELECT subject_id, subject_name 
  FROM subjects 
  WHERE teacher_id = ? 
    AND hash = ?
");

if (!$stmt) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error']);
  exit;
}

$stmt->bind_param("is", $teacherId, $hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  // Duplicate found
  $row = $result->fetch_assoc();
  echo json_encode([
    'status' => 'duplicate',
    'message' => 'มีรายวิชาที่ซ้ำซ้อน',
    'existing_subject' => $row['subject_name']
  ]);
} else {
  // No duplicate
  echo json_encode([
    'status' => 'ok',
    'message' => 'ไม่พบการซ้ำซ้อน',
    'hash' => $hash
  ]);
}

?>
