<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

ob_clean();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require __DIR__ . "/../config.php";

// Ensure all date/time operations use Thailand time
date_default_timezone_set('Asia/Bangkok');

function response($arr){
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) response(["message"=>"Invalid JSON"]);

$token = trim($data['token'] ?? '');
$lineId = $data['line_user_id'] ?? '';

if (!$token) response(["message"=>"QR ไม่ถูกต้อง"]);

$stmt = $conn->prepare("
  SELECT * FROM attendance_sessions
  WHERE qr_token=? AND is_active=1
");
$stmt->bind_param("s", $token);
$stmt->execute();

$session = $stmt->get_result()->fetch_assoc();
if (!$session) response(["message"=>"QR ไม่ถูกต้อง"]);

$stmt = $conn->prepare("
  SELECT s.user_id
  FROM users u
  JOIN students s ON u.id=s.user_id
  WHERE u.line_user_id=?
");
$stmt->bind_param("s", $lineId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
if (!$student) response(["message"=>"ยังไม่ได้ลงทะเบียน"]);

$stmt = $conn->prepare("
  SELECT checkin_time, checkout_time, checkin_status, checkout_status 
  FROM attendance_logs
  WHERE session_id=? AND student_id=?
");
$stmt->bind_param("ii", $session['id'], $student['user_id']);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();

if (!$attendance) {
  response([
    "has_checked_in" => false,
    "has_checked_out" => false,
    "can_checkout" => false
  ]);
}

$now = new DateTime('now', new DateTimeZone('Asia/Bangkok'));
$checkoutStartDt = new DateTime($session['checkout_start'] ?: $session['end_time'], new DateTimeZone('Asia/Bangkok'));

$canCheckout = ($now >= $checkoutStartDt);
$hasCheckedOut = !is_null($attendance['checkout_time']);

response([
  "has_checked_in" => true,
  "checkin_time" => $attendance['checkin_time'],
  "checkin_status" => $attendance['checkin_status'],
  "has_checked_out" => $hasCheckedOut,
  "checkout_time" => $attendance['checkout_time'],
  "checkout_status" => $attendance['checkout_status'],
  "can_checkout" => $canCheckout,
  "checkout_start_time" => $session['checkout_start'] ?: $session['end_time'],
  "session_end_time" => $session['end_time']
]);

