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

$token   = trim($data['token'] ?? '');
$lineId  = $data['line_user_id'] ?? '';
$lat     = floatval($data['lat'] ?? 0);
$lng     = floatval($data['lng'] ?? 0);
$accuracy= floatval($data['accuracy'] ?? 999);

if (!$token) response(["message"=>"QR ไม่ถูกต้อง"]);

$stmt = $conn->prepare("
  SELECT * FROM attendance_sessions
  WHERE qr_token=? AND is_active=1
");
if (!$stmt) response(["message"=>"Prepare failed"]);

$stmt->bind_param("s", $token);
$stmt->execute();

$session = $stmt->get_result()->fetch_assoc();
if (!$session) response(["message"=>"QR ไม่ถูกต้อง"]);

$now = (new DateTime('now', new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s');

// Check if current time is past the checkout_start time
try {
  $nowDt = new DateTime($now, new DateTimeZone('Asia/Bangkok'));
  $checkoutStartDt = new DateTime($session['checkout_start'] ?: $session['end_time'], new DateTimeZone('Asia/Bangkok'));
  if ($nowDt < $checkoutStartDt) {
    response(["message"=>"ยังไม่ถึงเวลาเช็คชื่อออก"]);
  }
} catch (Exception $e) {
  if ($now < $session['end_time']) {
    response(["message"=>"ยังไม่ถึงเวลาเช็คชื่อออก"]);
  }
}

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
  SELECT id, checkout_time FROM attendance_logs
  WHERE session_id=? AND student_id=?
");
$stmt->bind_param("ii", $session['id'], $student['user_id']);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if (!$existing) {
  response(["message"=>"ไม่พบบันทึกเช็คชื่อเข้า"]);
}

if ($existing['checkout_time']) {
  response(["message"=>"คุณเช็คชื่อออกแล้ว"]);
}

function distance($lat1,$lon1,$lat2,$lon2){
  $earth=6371000;
  $dLat=deg2rad($lat2-$lat1);
  $dLon=deg2rad($lon2-$lon1);
  $a=sin($dLat/2)**2 +
     cos(deg2rad($lat1))*cos(deg2rad($lat2))*
     sin($dLon/2)**2;
  return $earth*2*atan2(sqrt($a),sqrt(1-$a));
}

$dist = distance(
  $lat,$lng,
  $session['latitude'],$session['longitude']
);

if ($dist > $session['radius_meter']) {
  response([
    "message"=>"อยู่นอกพื้นที่ห้องเรียน",
    "distance"=>round($dist,2)
  ]);
}

// Update checkout information
$logId = intval($existing['id']);
$checkoutStatus = 'checked-out';

$update = $conn->prepare(
  "UPDATE attendance_logs SET checkout_time=?, checkout_status=? WHERE id=?"
);
if (!$update) response(["message"=>"Prepare failed"]);
$update->bind_param("ssi", $now, $checkoutStatus, $logId);
if (!$update->execute()) {
  response(["message"=>"ไม่สามารถบันทึกข้อมูลออกได้"]);
}

response(["message"=>"เช็คชื่อออกสำเร็จ ✅"]);

