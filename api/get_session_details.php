<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

ob_clean();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . "/../config.php";

function response($arr){
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) response(["error"=>"Invalid JSON"]);

$token = trim($data['token'] ?? '');
$lineId = $data['line_user_id'] ?? '';

if (!$token) response(["error"=>"QR ไม่ถูกต้อง"]);

$stmt = $conn->prepare("
  SELECT 
    s.id, 
    s.subject_name, 
    s.room_name, 
    s.checkin_start, 
    s.checkin_deadline, 
    s.checkout_start, 
    s.checkout_deadline,
    s.subject_id,
    s.teacher_id,
    subj.subject_code,
    subj.section,
    t.full_name as teacher_name
  FROM attendance_sessions s
  LEFT JOIN subjects subj ON s.subject_id = subj.subject_id
  LEFT JOIN teachers t ON s.teacher_id = t.id
  WHERE s.qr_token=? AND s.is_active=1
");
$stmt->bind_param("s", $token);
$stmt->execute();

$session = $stmt->get_result()->fetch_assoc();
if (!$session) response(["error"=>"QR ไม่ถูกต้อง"]);

$stmt = $conn->prepare("
  SELECT s.user_id, s.student_code, s.full_name
  FROM users u
  JOIN students s ON u.id=s.user_id
  WHERE u.line_user_id=?
");
$stmt->bind_param("s", $lineId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
if (!$student) response(["error"=>"ยังไม่ได้ลงทะเบียน"]);

response([
  "success" => true,
  "student_code" => $student['student_code'],
  "full_name" => $student['full_name'],
  "subject_name" => $session['subject_name'],
  "subject_code" => $session['subject_code'] ?? "-",
  "section" => $session['section'] ?? "-",
  "teacher_name" => $session['teacher_name'] ?? "-",
  "room_name" => $session['room_name'],
  "session_id" => $session['id'],
  "checkin_start" => $session['checkin_start'],
  "checkin_deadline" => $session['checkin_deadline'],
  "checkout_start" => $session['checkout_start'],
  "checkout_deadline" => $session['checkout_deadline']
]);